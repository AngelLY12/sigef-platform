<?php

namespace App\Core\Application\UseCases\Admin\RolePermissionManagement;

use App\Core\Application\DTO\Request\User\UpdateUserRoleDTO;
use App\Core\Application\DTO\Response\User\UserWithUpdatedRoleResponse;
use App\Core\Application\Mappers\UserMapper;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Repositories\Command\Auth\RolesAndPermissionsRepInterface;
use App\Core\Domain\Repositories\Query\Auth\RolesAndPermissosQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Exceptions\NotAllowed\AdminRoleNotAllowedException;
use App\Exceptions\NotFound\RoleNotFoundException;
use App\Exceptions\NotFound\UsersNotFoundForUpdateException;
use App\Exceptions\Validation\ValidationException;
use Illuminate\Support\Collection;

class SyncRoleUseCase
{
    private const CHUNK_SIZE = 100;
    public function __construct(
        private RolesAndPermissionsRepInterface $repo,
        private RolesAndPermissosQueryRepInterface $rpqRepo,
        private UserQueryRepInterface $uqRepo
    )
    {

    }

    public function execute(UpdateUserRoleDTO $dto): UserWithUpdatedRoleResponse
    {
        $this->validateNoDuplicateRoles($dto);
        $updated=$this->updateRoleToMany($dto);
        if ($updated === null)
        {
            throw new UsersNotFoundForUpdateException();
        }
        return $updated;
    }

    private function validateNoDuplicateRoles(UpdateUserRoleDTO $dto): void
    {
        $add = $dto->rolesToAdd ?? [];
        $remove = $dto->rolesToRemove ?? [];

        $duplicates = array_intersect($add, $remove);

        if (!empty($duplicates)) {
            throw new ValidationException(
                "Los siguientes roles no pueden estar simultáneamente en add y remove: "
                . implode(', ', $duplicates)
            );
        }
    }

    private function updateRoleToMany(UpdateUserRoleDTO $dto): ?UserWithUpdatedRoleResponse
    {
        if (empty($dto->curps) || (empty($dto->rolesToAdd) && empty($dto->rolesToRemove))) {
            return null;
        }

        $users = $this->uqRepo->getUsersByCurpCursor($dto->curps);

        $users = $this->processUsersFromGenerator($users);

        if ($users->isEmpty()) {
            return null;
        }
        $rolesToAddIds = $this->rpqRepo->getRoleIdsByNames($dto->rolesToAdd);
        $rolesToRemoveIds = $this->rpqRepo->getRoleIdsByNames($dto->rolesToRemove);

        if (empty($rolesToAddIds) && empty($rolesToRemoveIds)) {
            throw new RoleNotFoundException();
        }

        $adminRole = $this->rpqRepo->findRoleByName(UserRoles::ADMIN->value);
        if (!$adminRole) {
            throw new RoleNotFoundException();
        }
        if ($this->hasAdminErrors($adminRole->id, $rolesToAddIds, $rolesToRemoveIds, $users)) {
            throw new AdminRoleNotAllowedException();
        }
        $rolesToRemoveIds = $this->handleUnverifiedRole($rolesToRemoveIds);
        $totalSync= $this->syncRolesInChunks($users, $rolesToAddIds, $rolesToRemoveIds);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        return  $this->buildResponse($users, $dto, $totalSync);
    }

    private function processUsersFromGenerator(\Generator $usersGenerator): Collection
    {
        $users = collect();
        $hasUsers = false;

        foreach ($usersGenerator as $user) {
            $hasUsers = true;
            $users->push($user);
        }

        return $hasUsers ? $users : collect();
    }

    private function hasAdminErrors(
        int $adminRoleId,
        array $rolesToAddIds,
        array $rolesToRemoveIds,
        Collection $users
    ): bool {
        return $this->rpqRepo->hasAdminAssignError($adminRoleId, $rolesToAddIds, $users)
            || $this->rpqRepo->hasAdminRemoveError($adminRoleId, $rolesToRemoveIds, $users)
            || $this->rpqRepo->hasAdminMissingError($adminRoleId, $rolesToRemoveIds, $rolesToAddIds);
    }

    private function handleUnverifiedRole(array $rolesToRemoveIds): array
    {
        $unverifiedRole = $this->rpqRepo->findRoleByName(UserRoles::UNVERIFIED->value);

        if ($unverifiedRole) {
            $rolesToRemoveIds[] = $unverifiedRole->id;
            $rolesToRemoveIds = array_unique($rolesToRemoveIds);
        }

        return $rolesToRemoveIds;
    }

    private function syncRolesInChunks(Collection $users, array $rolesToAddIds, array $rolesToRemoveIds): array
    {
        $totalResult = [
            'removed' => 0,
            'added' => 0,
            'users_affected_count' => 0,
            'users_unchanged_count' => 0,
            'users_failed_count' => 0,
            'users_affected' => [],
            'users_unchanged' => [],
            'users_failed' => [],
            'total_chunks' => 0
        ];

        $callback = function (Collection $chunk) use ($rolesToAddIds, $rolesToRemoveIds, &$totalResult) {
            $resultado = $this->repo->syncRoles($chunk, $rolesToAddIds, $rolesToRemoveIds);

            $totalResult['removed'] += $resultado['removed'];
            $totalResult['added'] += $resultado['added'];
            $totalResult['users_affected'] = array_values(array_unique(array_merge($totalResult['users_affected'],$resultado['users_affected'])));
            $totalResult['users_unchanged'] = array_values(array_unique(array_merge($totalResult['users_unchanged'],$resultado['users_unchanged'])));
            $totalResult['total_chunks']++;
        };

        if ($users->count() > self::CHUNK_SIZE) {
            $users->chunk(self::CHUNK_SIZE)->each($callback);
        } else {
            $callback($users);
        }
        $allIds = array_unique($users->pluck('id')->toArray());
        $processedIds = array_merge(
            $totalResult['users_affected'],
            $totalResult['users_unchanged']
        );
        $totalResult['users_affected_count'] = count($totalResult['users_affected']);
        $totalResult['users_unchanged_count'] = count($totalResult['users_unchanged']);
        $totalResult['users_failed'] = array_diff($allIds, $processedIds);
        $totalResult['users_failed_count'] = count($totalResult['users_failed']);
        return $totalResult;
    }

    private function buildResponse(Collection $users, UpdateUserRoleDTO $dto,  array $totalSync): UserWithUpdatedRoleResponse
    {
        return UserMapper::toUserWithUptadedRoleResponse(
            summary: [
                'totalFound' => $users->count(),
                'totalUpdated' => $totalSync['users_affected_count'],
                'totalUnchanged' => $totalSync['users_unchanged_count'],
                'totalFailed' => $totalSync['users_failed_count'],
                'operations' => [
                    'total_roles_removed' => $totalSync['removed'],
                    'total_roles_added' => $totalSync['added'],
                    'total_chunks_processed' => $totalSync['total_chunks'],
                ],
                'unverified_policy' => [
                    'auto_removed' => true,
                    'reason' => 'UNVERIFIED rol siempre es removido al actualizar roles',
                ],
            ],
            usersProcessed: [
                'processed_users_id' => array_slice($users->pluck('id')->toArray(),0,10),
                'affected_users_id' => array_slice($totalSync['users_affected'], 0, 10),
                'unchanged_users_id' => $totalSync['users_unchanged'],
                'failed_users_id' => $totalSync['users_failed'],
            ] ,
            updatedRoles: [
                'processed_added' => $dto->rolesToAdd ?? [],
                'processed_removed' => $dto->rolesToRemove ?? []
            ]
        );
    }
}
