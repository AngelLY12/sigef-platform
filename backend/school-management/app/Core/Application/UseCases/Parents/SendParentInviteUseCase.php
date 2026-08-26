<?php

namespace App\Core\Application\UseCases\Parents;

use App\Core\Application\Factories\Emails\Events\EmailEventFactory;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Mappers\ParentInviteMapper;
use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Domain\Entities\EmailEvent;
use App\Core\Domain\Entities\ParentInvite;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Repositories\Command\Misc\ParentInviteRepInterface;
use App\Core\Domain\Repositories\Query\User\ParentStudentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Utils\Helpers\EventSourceId;
use App\Exceptions\Conflict\RelationAlreadyExistsException;
use App\Exceptions\NotFound\UserNotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\Jobs\SendMailJob;
use App\Mail\SendParentInviteEmail;

class SendParentInviteUseCase
{
    public function __construct(
        private ParentInviteRepInterface $inviteRepo,
        private ParentStudentQueryRepInterface $relationQRepo,
        private UserQueryRepInterface $userQRepo,
        private EmailEventManagerInterface $emailEventManager
    ) {}

    public function execute(int $studentId, string $parentEmail, int $createdBy): ParentInvite
    {
        $student=$this->userQRepo->findById($studentId);
        $parent=$this->userQRepo->findUserByEmail($parentEmail);
        $operationId = EventSourceId::generateOperationId();
        if(!$student || !$parent)
        {
            throw new UserNotFoundException();
        }

        if($student->email === $parentEmail){
            throw new ValidationException("No puedes invitarte a ti mismo");
        }

        if ($this->relationQRepo->exists($parent->id, $studentId)) {
            throw new RelationAlreadyExistsException();
        }
        $operationId = EventSourceId::generateOperationId();
        $event = $this->emailEventManager->findOrCreate(
            eventType: EmailEventType::PARENT_INVITED,
            sourceType: EmailEventSourceType::USER,
            sourceId: EventSourceId::email(
                sourceType: EmailEventSourceType::USER,
                eventType: EmailEventType::PARENT_INVITED,
                operationId: $operationId,
                recipientId: $parent->id,
            ),
            factory: fn() => EmailEventFactory::parentInvited(user:$parent,operationId: $operationId)
        );
        $invite= ParentInviteMapper::toDomain(
            [
                'studentId'=>$student->id,
                'parentEmail' => $parentEmail,
                'createdBy' => $createdBy
            ]
        );
        $invite = $this->inviteRepo->create($invite);
        $this->notifyRecipients($parent, $invite->token, $event);
        return $invite;
    }

    private function notifyRecipients(User $user, string $token, EmailEvent $event): void {
            $this->emailEventManager->dispatch(
                event: $event,
                mail: new SendParentInviteEmail(
                    MailMapper::toSendParentInviteEmail(
                        fullName: $user->fullName(),
                        email: $user->email,
                        token: $token
                    )
                ),
                recipientEmail: $user->email,
                jobType: 'parent_invitation'
            );
    }
}
