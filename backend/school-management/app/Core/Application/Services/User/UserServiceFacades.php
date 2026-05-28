<?php

namespace App\Core\Application\Services\User;

use App\Core\Application\DTO\Response\StudentDetail\StudentDetailDTO;
use App\Core\Application\DTO\Response\User\UserAuthResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\User\FindStudentDetailsUseCase;
use App\Core\Application\UseCases\User\FindUserUseCase;
use App\Core\Application\UseCases\User\UpdateUserUseCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\AdminCacheSufix;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Utils\Validators\UserValidator;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\NotAllowed\InvalidCurrentPasswordException;
use Illuminate\Support\Facades\Hash;

class UserServiceFacades
{
    use HasCache;

    private const TAG_USER = [CachePrefix::USER->value, "profile"];
    private const TAG_STUDENT_DETAILS = [CachePrefix::USER->value, "student-details"];
    private const TAG_USERS_ID = [CachePrefix::ADMIN->value, AdminCacheSufix::USERS->value, "all:id"];

    public function __construct(
            private UpdateUserUseCase $update,
            private CacheService $service,
            private UserQueryRepInterface $userRepo,
            private FindUserUseCase $user,
            private FindStudentDetailsUseCase $studentDetails,

    )
    {
        $this->setCacheService($service);
    }

    public function findUser(bool $forceRefresh): UserAuthResponse
    {
        return $this->user->execute($forceRefresh);
    }

    public function findStudentDetails(int $userId, bool $forceRefresh): StudentDetailDTO
    {
        $key = $this->generateCacheKey(
            CachePrefix::USER->value,
            "student-details",
            ["userId"=>$userId]
        );
        $tags = array_merge(self::TAG_STUDENT_DETAILS, ["userId:{$userId}"]);
        return $this->longCache($key, fn() => $this->studentDetails->execute($userId),$tags,$forceRefresh);
    }

    public function updateUser(int $userId, array $fields): User
    {
        return $this->idempotent(
            'user_update',
            [
                'user_id' => $userId,
                'fields' => $fields,
            ],
            function () use ($userId, $fields) {

                $user = $this->userRepo->findById($userId);
                UserValidator::ensureUserIsValidToUpdate($user);

                $updatedUser = $this->update->execute($userId, $fields);

                $this->service->flushTags(array_merge(self::TAG_USERS_ID, ["userId:$userId"]));
                $this->service->flushTags(array_merge(self::TAG_USER, ["userId:$userId"]));

                return $updatedUser;
            },
            30
        );
    }

    public function updatePassword(int $userId, string $currentPassword, string $newPassword): User
    {
        return $this->idempotent(
            'user_update_password',
            [
                'user_id' => $userId,
                'current_password' => $currentPassword,
                'new_password' => $newPassword,
            ],
            function () use ($userId, $currentPassword, $newPassword) {

                $user = $this->userRepo->findById($userId);
                UserValidator::ensureUserIsValidToUpdate($user);

                if (!Hash::check($currentPassword, $user->password)) {
                    throw new InvalidCurrentPasswordException();
                }

                $hashed = Hash::make($newPassword);
                $updatedUser = $this->update->execute($userId, ['password' => $hashed]);

                $this->service->flushTags(array_merge(self::TAG_USER, ["userId:$userId"]));

                return $updatedUser;
            },
            60
        );
    }
}
