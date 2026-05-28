<?php

namespace App\Core\Application\UseCases\Admin\Shared;

use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Exceptions\NotFound\UsersNotFoundForUpdateException;
use App\Jobs\ClearStaffCacheJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class BaseChangeUserStatusUseCase
{
    protected const CHUNK_SIZE = 500;

    public function __construct(
        protected UserRepInterface $userRepo,
        protected UserQueryRepInterface $uqRepo,
    ) {}

    abstract protected function getTargetStatus(): UserStatus;
    abstract protected function validateUsers(iterable $users): void;

    protected function shouldClearCache(): bool
    {
        return true;
    }

    public function execute(array $ids): UserChangedStatusResponse
    {
        if (empty($ids)) {
            throw new UsersNotFoundForUpdateException();
        }

        $allValidUsers = $this->validateAndFilterUsers($ids);

        if ($allValidUsers->isEmpty()) {
            throw new UsersNotFoundForUpdateException();
        }

        $validIds = $allValidUsers->pluck('id')->toArray();
        $response=DB::transaction(function () use ($validIds) {
            $response = $this->userRepo->changeStatus($validIds, $this->getTargetStatus()->value);
            if($this->getTargetStatus() === UserStatus::ELIMINADO)
            {
                $this->userRepo->revokeTokensByUserIds($validIds);
            }
            return $response;
        });

        if ($this->shouldClearCache()) {
            $this->dispatchCacheClear();
        }

        return $response;
    }

    protected function validateAndFilterUsers(array $ids): Collection
    {
        $allValidUsers = collect();
        $idsChunks = array_chunk(array_unique($ids), static::CHUNK_SIZE);

        foreach ($idsChunks as $idsChunk) {
            $users = $this->uqRepo->findByIds($idsChunk);
            $this->validateUsers($users);
            $allValidUsers = $allValidUsers->merge($users);
        }

        return $allValidUsers;
    }

    protected function dispatchCacheClear(): void
    {
        ClearStaffCacheJob::dispatch()
            ->onQueue('cache');
    }

}
