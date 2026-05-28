<?php

namespace App\Core\Application\UseCases\Parents;

use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Mappers\ParentInviteMapper;
use App\Core\Domain\Entities\ParentInvite;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Command\Misc\ParentInviteRepInterface;
use App\Core\Domain\Repositories\Query\User\ParentStudentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
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
    ) {}

    public function execute(int $studentId, string $parentEmail, int $createdBy): ParentInvite
    {
        $student=$this->userQRepo->findById($studentId);
        $parent=$this->userQRepo->findUserByEmail($parentEmail);
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
        $invite= ParentInviteMapper::toDomain(
            [
                'studentId'=>$student->id,
                'parentEmail' => $parentEmail,
                'createdBy' => $createdBy
            ]
        );
        $invite = $this->inviteRepo->create($invite);
        $this->notifyRecipients($parent, $invite->token);
        return $invite;
    }

    private function notifyRecipients(User $user, string $token): void {
            $dtoData = [
                'recipientName'  => $user->fullName(),
                'recipientEmail' => $user->email,
                'token'       => $token
            ];

            SendMailJob::forUser(
                new SendParentInviteEmail(
                    MailMapper::toSendParentInviteEmail($dtoData)
                ),
                $user->email,
                'parent_invitation'
            )
                ->onQueue('emails');

    }
}
