<?php

namespace App\Core\Application\UseCases\Auth;

use App\Core\Application\DTO\Request\General\LoginDTO;
use App\Core\Application\DTO\Response\General\LoginResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Utils\Validators\UserValidator;
use App\Exceptions\Unauthorized\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;

class LoginUseCase
{
    public function __construct(
        private UserRepInterface $userRepo,
        private UserQueryRepInterface $uqRepo,
    )
    {
   }

   public function execute(LoginDTO $request): LoginResponse
   {

        $user=$this->uqRepo->findUserByEmail($request->email);
        if(!$user)
        {
            throw new InvalidCredentialsException();
        }
        UserValidator::ensureUserIsActive($user);

        $passwordValid = $user ? Hash::check($request->password, $user->password) : false;

        if (!$passwordValid) {
           throw new InvalidCredentialsException();
        }
       $hasUnreadNotifications = $this->uqRepo->userHasUnreadNotifications($user->id);
       $userData=$this->formatUserData($user, $hasUnreadNotifications);
        $token = $this->userRepo->createToken($user->id,'api-token');
        $refreshToken = $this->userRepo->createRefreshToken($user->id, 'refresh-token');
        return GeneralMapper::toLoginResponse($token,$refreshToken,'Bearer', $userData);
   }

   private function formatUserData(User $user, bool $hasUnreadNotifications): array
   {
       return [
            'id' => $user->id,
            'fullName' => $user->fullName(),
            'status' => $user->status->value,
            'roles' => $user->getRoleNames(),
            'hasUnreadNotifications' => $hasUnreadNotifications
        ];
   }
}
