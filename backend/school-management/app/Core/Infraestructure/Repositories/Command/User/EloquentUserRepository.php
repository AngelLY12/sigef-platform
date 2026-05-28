<?php

namespace App\Core\Infraestructure\Repositories\Command\User;

use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Domain\Entities\User;
use App\Models\User as EloquentUser;
use App\Core\Infraestructure\Mappers\UserMapper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Application\Mappers\UserMapper as AppUserMapper;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use Illuminate\Support\Collection;

class EloquentUserRepository implements UserRepInterface
{

    public function create(CreateUserDTO $user): \App\Models\User
    {
         $eloquentUser = EloquentUser::create(
            UserMapper::toPersistence($user)
        );
        $eloquentUser->refresh();

        return $eloquentUser;
    }

    public function update(int $userId, array $fields): User
    {
        $eloquentUser =  $this->findOrFail($userId);
        $eloquentUser->update($fields);
        return UserMapper::toDomain($eloquentUser);
    }

    public function createToken(int $userId, string $name): string
    {
        $eloquentUser = $this->findOrFail($userId);
        return $eloquentUser->createToken($name, expiresAt:now()->addMinutes(30))->plainTextToken;
    }

    public function createRefreshToken(int $userId, string $name): string
    {
        $eloquentUser = $this->findOrFail($userId);
        $refreshToken = bin2hex(random_bytes(64));
        $role = $eloquentUser->getRoleNames()
            ->sortBy(fn ($role) => match ($role) {
                UserRoles::ADMIN->value,
                UserRoles::SUPERVISOR->value => 1,
                UserRoles::FINANCIAL_STAFF->value => 2,
                UserRoles::UNVERIFIED->value => 0,
                default => 3,
            })

            ->first();
        $expirationTime = config("refresh-token.expiration_time_by_role.$role") ??
            config('refresh-token.default_refresh_ttl');
        $eloquentUser->refreshTokens()->create([
            'token' => hash('sha256', $refreshToken),
            'expires_at' => now()->addMinutes($expirationTime),
            'revoked' => false
        ]);
        return $refreshToken;
    }

    private function findOrFail(int $id):EloquentUser
    {
        return EloquentUser::findOrFail($id);
    }

    public function assignRole(int $userId, string $role): bool
    {
        $user = EloquentUser::with('roles')->find($userId);

        if (! $user) {
            return false;
        }
        $user->assignRole($role);
        if ($role !== UserRoles::UNVERIFIED->value && $user->hasRole(UserRoles::UNVERIFIED->value)) {
            $user->removeRole(UserRoles::UNVERIFIED->value);
        }
        $user->refresh();
        return true;
    }

    public function insertManyUsers(array $usersData): Collection {
        DB::table('users')->insert($usersData);
        $emails = collect($usersData)->pluck('email')->toArray();
        return EloquentUser::whereIn('email', $emails)->get();
    }

    public function insertSingleUser(array $userData): EloquentUser
    {
        try {
            return EloquentUser::create($userData);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deletionEliminateUsers(): int
    {
        $thresholdDate = Carbon::now()->subDays(30);

        return DB::transaction(function () use ($thresholdDate) {
            $userIds = DB::table('users')
                ->where('status', '=', UserStatus::ELIMINADO)
                ->whereNotNull('mark_as_deleted_at')
                ->where('mark_as_deleted_at', '<', $thresholdDate)
                ->pluck('id');

            if ($userIds->isEmpty()) {
                return 0;
            }
            DB::table('notifications')
                ->where('notifiable_type', 'App\Models\User')
                ->whereIn('notifiable_id', $userIds)
                ->delete();

            return DB::table('users')
                ->whereIn('id', $userIds)
                ->delete();
        });
    }
    public function changeStatus(array $userIds, string $status): UserChangedStatusResponse
    {
        if (empty($userIds)) {
            return new UserChangedStatusResponse($status, 0);
        }

        $affected = EloquentUser::whereIn('id', $userIds)
            ->where('status', '!=', $status)
            ->update([
                'status' => $status,
                'updated_at' => now(),
                'mark_as_deleted_at' => $status === UserStatus::ELIMINADO->value ? now() : null,
            ]);


        if ($affected === 0) {
            return new UserChangedStatusResponse($status, 0);
        }

        return AppUserMapper::toUserChangedStatusResponse([
            'status' => $status,
            'total'  => $affected
        ]);
    }

    public function revokeTokensByUserIds(array $userIds): int
    {
        $sanctumDeleted = DB::table('personal_access_tokens')
            ->whereIn('tokenable_id', $userIds)
            ->delete();
        $refreshTokenDeleted = DB::table('refresh_tokens')
            ->whereIn('user_id', $userIds)
            ->delete();
        return $sanctumDeleted + $refreshTokenDeleted;
    }

}
