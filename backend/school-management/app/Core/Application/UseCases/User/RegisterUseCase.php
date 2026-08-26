<?php

namespace App\Core\Application\UseCases\User;

use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\Factories\Emails\Events\EmailEventFactory;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Domain\Entities\EmailEvent;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Enum\User\UserActorType;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Domain\Utils\Helpers\EventSourceId;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Jobs\SendMailJob;
use App\Mail\CreatedUserMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterUseCase
{
    public function __construct(
        private UserRepInterface $userRepo,
        private EmailEventManagerInterface $emailEventManager,
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

        $domainUser =  UserMapper::toDomain($user);

        if($password) {
            $operationId = EventSourceId::generateOperationId();
            $event = $this->emailEventManager->findOrCreate(
                eventType: EmailEventType::USER_CREATED,
                sourceType: EmailEventSourceType::USER,
                sourceId: EventSourceId::email(
                    sourceType: EmailEventSourceType::USER,
                    eventType: EmailEventType::USER_CREATED,
                    operationId: $operationId,
                    recipientId: $domainUser->id,
                ),
                factory: fn () => EmailEventFactory::userCreated(
                    user: $domainUser,
                    actorType: UserActorType::ADMIN,
                    operationId: $operationId,
                ),
            );
            $this->notifyRecipients($user, $password, $event);
        } else {
            event(new Registered($user));
        }
        return $domainUser;
    }

    private function notifyRecipients(\App\Models\User $user, $password, EmailEvent $event): void {
            $mail = new CreatedUserMail(
                MailMapper::toNewUserCreatedEmailDTO(
                    fullName: $user->name . ' ' . $user->last_name,
                    email: $user->email,
                    password: $password
                )
            );
            $this->emailEventManager->dispatch(
                event: $event,
                mail: $mail,
                recipientEmail: $user->email,
                jobType: 'register_user'
            );
    }
}
