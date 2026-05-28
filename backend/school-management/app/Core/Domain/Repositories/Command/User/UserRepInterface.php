<?php

namespace App\Core\Domain\Repositories\Command\User;

use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Domain\Entities\User;
use App\Models\User as EloquentUser;
use Illuminate\Support\Collection;

interface UserRepInterface{
    public function create(CreateUserDTO $user):\App\Models\User;
    public function update(int $userId, array $fields):User;
    public function changeStatus(array $userIds,string $status): UserChangedStatusResponse;
    public function insertManyUsers(array $usersData): Collection;
    public function insertSingleUser(array $userData): EloquentUser;
    public function deletionEliminateUsers(): int;
    public function createToken(int $userId, string $name): string;
    public function assignRole(int $userId, string $role): bool;
    public function createRefreshToken(int $userId, string $name): string;
    public function revokeTokensByUserIds(array $userIds): int;

}
