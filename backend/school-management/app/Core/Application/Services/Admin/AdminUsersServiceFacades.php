<?php

namespace App\Core\Application\Services\Admin;

use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\DTO\Response\General\ImportResponse;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Application\DTO\Response\User\UserExtraDataResponse;
use App\Core\Application\DTO\Response\User\UsersAdminSummary;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Admin\UserManagement\ActivateUserUseCase;
use App\Core\Application\UseCases\Admin\UserManagement\BulkImportUsersUseCase;
use App\Core\Application\UseCases\Admin\UserManagement\DeleteLogicalUserUseCase;
use App\Core\Application\UseCases\Admin\UserManagement\DisableUserUseCase;
use App\Core\Application\UseCases\Admin\UserManagement\GetExtraUserDataUseCase;
use App\Core\Application\UseCases\Admin\UserManagement\ShowAllUsersUseCase;
use App\Core\Application\UseCases\Admin\UserManagement\TemporaryDisableUserUseCase;
use App\Core\Application\UseCases\Admin\UserManagement\UsersAdminSummaryUseCase;
use App\Core\Application\UseCases\User\RegisterUseCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\AdminCacheSufix;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Infraestructure\Cache\CacheService;

class AdminUsersServiceFacades
{
    use HasCache;
    private const TAG_USERS_ALL = [CachePrefix::ADMIN->value, AdminCacheSufix::USERS->value, "all"];
    private const TAG_USERS_ID = [CachePrefix::ADMIN->value, AdminCacheSufix::USERS->value, "all:id"];

    public function __construct(
        private RegisterUseCase                 $register,
        private BulkImportUsersUseCase           $import,
        private ShowAllUsersUseCase              $show,
        private GetExtraUserDataUseCase          $extraData,
        private ActivateUserUseCase              $activate,
        private DeleteLogicalUserUseCase         $delete,
        private DisableUserUseCase               $disable,
        private TemporaryDisableUserUseCase      $temporaryDisable,
        private UsersAdminSummaryUseCase $usersSummary,
        private CacheService                     $service
    )
    {
        $this->setCacheService($service);
    }

    public function registerUser(CreateUserDTO $user, string $password):User
    {
        return $this->idempotent(
            'users_register',
            [
                'curp' => $user->curp,
                'email' => $user->email,
            ],
            function () use ($user, $password) {

                $createdUser = $this->register->execute($user, $password);
                $this->service->flushTags(self::TAG_USERS_ALL);

                return $createdUser;
            }
        );
    }

    public function importUsers(array $rows): ImportResponse
    {
        return $this->idempotent(
            'users_import',
            [
                'rows_hash' => hash('sha256', json_encode($rows)),
                'count' => count($rows),
            ],
            function () use ($rows) {

                $import = $this->import->execute($rows);
                $this->service->flushTags(self::TAG_USERS_ALL);

                return $import;
            },
            600
        );
    }

    public function showUsersSummary(bool $onlyThisYear, bool $forceRefresh): UsersAdminSummary
    {
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::USERS->value . ":summary",
            ['onlyThisYear' => $onlyThisYear]
        );
        return $this->longCache($key, fn() => $this->usersSummary->execute($onlyThisYear), self::TAG_USERS_ALL, $forceRefresh);
    }

    public function showAllUsers(int $perPage, int $page, bool $forceRefresh, ?UserStatus $status = null): PaginatedResponse
    {
        $statusValue = $status ? $status->value : 'all';
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::USERS->value . ":all",
            ['page' => $page, 'perPage' => $perPage, 'status' => $statusValue]
        );
        return $this->longCache($key, fn() => $this->show->execute($perPage, $page, $status),self::TAG_USERS_ALL,$forceRefresh);
    }

    public function getExtraUserData(int $userId, bool $forceRefresh): UserExtraDataResponse
    {
        $key = $this->generateCacheKey(CachePrefix::ADMIN->value, AdminCacheSufix::USERS->value . ":all:id",["userId" => $userId]);
        $tags= array_merge(self::TAG_USERS_ID, ["userId:{$userId}"]);
        return $this->shortCache($key, fn() => $this->extraData->execute($userId), $tags ,$forceRefresh);
    }

    public function activateUsers(array $ids): UserChangedStatusResponse
    {
        return $this->idempotent(
            'users_activate',
            ['ids' => $ids],
            function () use ($ids) {

                $users = $this->activate->execute($ids);
                $this->service->flushTags(self::TAG_USERS_ALL);

                return $users;
            }
        );
    }
     public function deleteUsers(array $ids): UserChangedStatusResponse
    {
        return $this->idempotent(
            'users_delete',
            ['ids' => $ids],
            function () use ($ids) {

                $users = $this->delete->execute($ids);
                $this->service->flushTags(self::TAG_USERS_ALL);

                return $users;
            }
        );
    }
     public function disableUsers(array $ids): UserChangedStatusResponse
    {
        return $this->idempotent(
            'users_disable',
            ['ids' => $ids],
            function () use ($ids) {

                $users = $this->disable->execute($ids);
                $this->service->flushTags(self::TAG_USERS_ALL);

                return $users;
            }
        );
    }

    public function temporaryDisableUsers(array $ids): UserChangedStatusResponse
    {
        return $this->idempotent(
            'users_temp_disable',
            ['ids' => $ids],
            function () use ($ids) {

                $users = $this->temporaryDisable->execute($ids);
                $this->service->flushTags(self::TAG_USERS_ALL);

                return $users;
            }
        );
    }

}
