<?php

namespace App\Core\Application\UseCases\User;

use App\Core\Application\DTO\Response\User\UserAuthResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\NotFound\UserNotFoundException;

class FindUserUseCase
{
    use HasCache;
    private const TAG_USER = [CachePrefix::USER->value, "profile"];

    public function __construct(
        private UserQueryRepInterface $uqRepo,
        CacheService $service
    )
    {
        $this->setCacheService($service);
    }
    public function execute(bool $forceRefresh): UserAuthResponse
    {

        $user =$this->uqRepo->findAuthUser();
        if(!$user)
        {
            throw new UserNotFoundException();
        }
        $key = $this->generateCacheKey(
            CachePrefix::USER->value,
            "profile",
            ["userId"=>$user->id]
        );
        $tags = array_merge(self::TAG_USER, ["userId:{$user->id}"]);
        return $this->weeklyCache($key, fn() => $user,$tags,$forceRefresh);
    }
}
