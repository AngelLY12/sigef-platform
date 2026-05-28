<?php

namespace App\Core\Application\UseCases\User;

use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Jobs\SendMailJob;
use App\Mail\CreatedUserMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterUseCase
{
    public function __construct(
        private UserRepInterface $userRepo
    )
    {}

    public function execute(CreateUserDTO $create, ?string $password= null): User
    {
        $user= DB::transaction(function () use ($create) {
            $user= $this->userRepo->create($create);
            $role= $this->userRepo->assignRole($user->id, UserRoles::UNVERIFIED->value);
            if(!$role){ throw new \RuntimeException("Hubo un fallo al agregar el rol al usuario {$user->id}");}
            return $user;
        });


        if($password) {
            $this->notifyRecipients($user, $password);
        } else {
            event(new Registered($user));
        }
        return UserMapper::toDomain($user);
    }

    private function notifyRecipients(\App\Models\User $user, $password): void {
            $dtoData = [
                'recipientName'  => $user->name . ' ' . $user->last_name,
                'recipientEmail' => $user->email,
                'password'       => $password
            ];

            SendMailJob::forUser(
                new CreatedUserMail(
                    MailMapper::toNewUserCreatedEmailDTO($dtoData)
                ),
                $user->email,
                'register_user'
            )
                ->onQueue('emails');

    }
}
