<?php
namespace App\Core\Application\Services\Auth;

use App\Core\Application\DTO\Request\General\LoginDTO;
use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\DTO\Response\General\LoginResponse;
use App\Core\Application\UseCases\Auth\LoginUseCase;
use App\Core\Application\UseCases\User\RegisterUseCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\AdminCacheSufix;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Infraestructure\Cache\CacheService;

class LoginServiceFacades
{
    private const TAG_USERS_ALL = [CachePrefix::ADMIN->value, AdminCacheSufix::USERS->value, "all"];

    public function __construct(
        private LoginUseCase $login,
        private RegisterUseCase $register,
        private CacheService $service
    )
    {
   }

   public function login(LoginDTO $request): LoginResponse
   {
        return $this->login->execute($request);
   }

   public function register(CreateUserDTO $user): User
   {
        $user= $this->register->execute($user);
        $this->service->flushTags(self::TAG_USERS_ALL);
        return $user;
   }
}
