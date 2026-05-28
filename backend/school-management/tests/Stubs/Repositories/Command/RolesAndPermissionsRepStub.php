<?php

namespace Tests\Stubs\Repositories\Command;

use App\Core\Domain\Repositories\Command\Auth\RolesAndPermissionsRepInterface;
use App\Models\User;
use Illuminate\Support\Collection;

class RolesAndPermissionsRepStub implements RolesAndPermissionsRepInterface
{
    private bool $throwDatabaseError = false;
    private array $userRoles = [];
    private array $userPermissions = [];
    private int $nextRoleId = 1;
    private int $nextPermissionId = 1;

    public function __construct()
    {
        $this->initializeTestData();
    }

    private function initializeTestData(): void
    {
        // Datos de prueba iniciales
        $this->userRoles = [
            1 => '1,2', // Usuario 1 tiene roles 1 y 2
            2 => '3',    // Usuario 2 tiene rol 3
            3 => '1,3,4', // Usuario 3 tiene roles 1, 3 y 4
        ];

        $this->userPermissions = [
            1 => '10,11,12', // Usuario 1 tiene permisos 10, 11, 12
            2 => '10,13',    // Usuario 2 tiene permisos 10, 13
            3 => '11,12,14', // Usuario 3 tiene permisos 11, 12, 14
        ];
    }

    public function assignRoles(array $roleRows): void
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (empty($roleRows)) {
            return;
        }

        foreach ($roleRows as $row) {
            $userId = $row['model_id'];
            $roleId = $row['role_id'];

            if (!isset($this->userRoles[$userId])) {
                $this->userRoles[$userId] = (string) $roleId;
            } else {
                $roles = explode(',', $this->userRoles[$userId]);
                if (!in_array($roleId, $roles)) {
                    $roles[] = $roleId;
                    $this->userRoles[$userId] = implode(',', $roles);
                }
            }
        }
    }

    public function givePermissionsByType(User $user, string $belongsTo, string $type = 'model'): void
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        // Simular que se asignan permisos basados en el tipo
        $permissionIds = [];

        switch ($belongsTo) {
            case 'school':
                $permissionIds = [101, 102, 103];
                break;
            case 'admin':
                $permissionIds = [201, 202, 203];
                break;
            default:
                $permissionIds = [301, 302];
        }

        $userId = $user->id;

        if (!isset($this->userPermissions[$userId])) {
            $this->userPermissions[$userId] = implode(',', $permissionIds);
        } else {
            $existingPermissions = explode(',', $this->userPermissions[$userId]);
            $newPermissions = array_merge($existingPermissions, $permissionIds);
            $this->userPermissions[$userId] = implode(',', array_unique($newPermissions));
        }
    }

    public function removePermissions(array $userIds, array $permissionIds): int
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (empty($permissionIds) || empty($userIds)) {
            return 0;
        }

        $removedCount = 0;

        foreach ($userIds as $userId) {
            if (isset($this->userPermissions[$userId])) {
                $existingPermissions = explode(',', $this->userPermissions[$userId]);
                $originalCount = count($existingPermissions);

                // Filtrar los permisos a eliminar
                $newPermissions = array_filter($existingPermissions, function($permId) use ($permissionIds) {
                    return !in_array($permId, $permissionIds);
                });

                $newCount = count($newPermissions);
                $removedCount += ($originalCount - $newCount);

                if ($newCount > 0) {
                    $this->userPermissions[$userId] = implode(',', $newPermissions);
                } else {
                    unset($this->userPermissions[$userId]);
                }
            }
        }

        return $removedCount;
    }

    public function addPermissions(array $userIds, array $permissionIds): int
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (empty($permissionIds) || empty($userIds)) {
            return 0;
        }

        $addedCount = 0;
        $uniquePermissionIds = array_unique($permissionIds);

        foreach ($userIds as $userId) {
            if (!isset($this->userPermissions[$userId])) {
                $this->userPermissions[$userId] = implode(',', $uniquePermissionIds);
                $addedCount += count($uniquePermissionIds);
            } else {
                $existingPermissions = explode(',', $this->userPermissions[$userId]);
                $newPermissions = [];

                foreach ($uniquePermissionIds as $permissionId) {
                    if (!in_array($permissionId, $existingPermissions)) {
                        $newPermissions[] = $permissionId;
                        $addedCount++;
                    }
                }

                if (!empty($newPermissions)) {
                    $allPermissions = array_merge($existingPermissions, $newPermissions);
                    $this->userPermissions[$userId] = implode(',', array_unique($allPermissions));
                }
            }
        }

        return $addedCount;
    }

    public function syncRoles(Collection $users, array $rolesToAddIds, array $rolesToRemoveIds): array
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $userIds = $users->pluck('id')->toArray();
        $removedCount = 0;
        $addedCount = 0;
        $usersAffected = 0;

        foreach ($userIds as $userId) {
            $hasChanges = false;

            // Remover roles
            if (!empty($rolesToRemoveIds) && isset($this->userRoles[$userId])) {
                $existingRoles = explode(',', $this->userRoles[$userId]);
                $originalCount = count($existingRoles);

                $newRoles = array_filter($existingRoles, function($roleId) use ($rolesToRemoveIds) {
                    return !in_array($roleId, $rolesToRemoveIds);
                });

                $newCount = count($newRoles);
                $removedFromUser = $originalCount - $newCount;
                $removedCount += $removedFromUser;

                if ($removedFromUser > 0) {
                    $hasChanges = true;
                    if ($newCount > 0) {
                        $this->userRoles[$userId] = implode(',', $newRoles);
                    } else {
                        unset($this->userRoles[$userId]);
                    }
                }
            }

            // Agregar roles
            if (!empty($rolesToAddIds)) {
                $existingRoles = isset($this->userRoles[$userId]) ? explode(',', $this->userRoles[$userId]) : [];

                $rolesToAdd = array_filter($rolesToAddIds, function($roleId) use ($existingRoles) {
                    return !in_array($roleId, $existingRoles);
                });

                if (!empty($rolesToAdd)) {
                    $hasChanges = true;
                    $allRoles = array_merge($existingRoles, $rolesToAdd);
                    $this->userRoles[$userId] = implode(',', array_unique($allRoles));
                    $addedCount += count($rolesToAdd);
                }
            }

            if ($hasChanges) {
                $usersAffected++;
            }
        }

        return [
            'removed' => $removedCount,
            'added' => $addedCount,
            'users_affected' => $usersAffected
        ];
    }

    public function getUsersPermissions(array $userIds): array
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $result = [];

        foreach ($userIds as $userId) {
            if (isset($this->userPermissions[$userId])) {
                $result[$userId] = $this->userPermissions[$userId];
            }
        }

        return $result;
    }

    public function getUsersRoles(array $userIds): array
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $result = [];

        foreach ($userIds as $userId) {
            if (isset($this->userRoles[$userId])) {
                $result[$userId] = $this->userRoles[$userId];
            }
        }

        return $result;
    }

    // Métodos de configuración para pruebas

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function setUserRoles(int $userId, array $roleIds): self
    {
        $this->userRoles[$userId] = implode(',', $roleIds);
        return $this;
    }

    public function setUserPermissions(int $userId, array $permissionIds): self
    {
        $this->userPermissions[$userId] = implode(',', $permissionIds);
        return $this;
    }

    public function getUserRoles(int $userId): ?array
    {
        if (!isset($this->userRoles[$userId])) {
            return null;
        }

        return explode(',', $this->userRoles[$userId]);
    }

    public function getUserPermissions(int $userId): ?array
    {
        if (!isset($this->userPermissions[$userId])) {
            return null;
        }

        return explode(',', $this->userPermissions[$userId]);
    }

    public function clearData(): self
    {
        $this->userRoles = [];
        $this->userPermissions = [];
        return $this;
    }

}
