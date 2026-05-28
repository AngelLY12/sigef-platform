<?php

namespace App\Core\Application\UseCases\Auth;

use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Command\Auth\RefreshTokenRepInterface;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Utils\Validators\TokenValidator;
use App\Core\Domain\Utils\Validators\UserValidator;
use App\Exceptions\NotFound\UserNotFoundException;
use App\Exceptions\Unauthorized\InvalidRefreshTokenException;
use App\Exceptions\Unauthorized\RefreshTokenRevokedException;

class RefreshTokenUseCase
{
    public function __construct(
        private RefreshTokenRepInterface $refresh,
        private UserRepInterface $userRepo,
        private UserQueryRepInterface $uqRepo
    )
    {
    }

    public function execute(string $refreshTokenValue)
    {
        $refreshToken= $this->refresh->findByToken($refreshTokenValue);
        if(!$refreshToken) throw new InvalidRefreshTokenException();
        TokenValidator::ensureIsTokenValid($refreshToken);
        $user = $this->uqRepo->findById($refreshToken->user_id);
        if(!$user) throw new UserNotFoundException();
        UserValidator::ensureUserIsActive($user);
        $revoked = $this->refresh->revokeRefreshToken($refreshTokenValue);
        if (! $revoked) {
            throw new InvalidRefreshTokenException('Ya se usó este refresh token');
        }
        $hasUnreadNotifications = $this->uqRepo->userHasUnreadNotifications($user->id);
        $userData=$this->formatUserData($user, $hasUnreadNotifications);
        $newAccessToken  = $this->userRepo->createToken($user->id, 'api-token');
        $newRefreshToken = $this->userRepo->createRefreshToken($user->id, 'refresh-token');
        return GeneralMapper::toLoginResponse($newAccessToken,
        $newRefreshToken,
        'Bearer',
        $userData);
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
