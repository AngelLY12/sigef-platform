<?php

namespace App\Core\Application\Traits;

use App\Core\Application\DTO\Request\PaymentConcept\CreatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Exceptions\NotFound\ExceptionStudentsNotFoundException;
use App\Exceptions\NotFound\StudentsNotFoundException;
use App\Jobs\ClearCacheForUsersJob;
use App\Jobs\SendBulkMailJob;
use App\Mail\NewConceptMail;

trait HasPaymentConcept
{

    private UserQueryRepInterface $repository;

    public function setRepository(UserQueryRepInterface $repository): void
    {
        $this->repository = $repository;
    }

    public function getUserIdListDTO(CreatePaymentConceptDTO|UpdatePaymentConceptRelationsDTO $dto, bool $exceptions=false): UserIdListDTO
    {
        $list = $exceptions
            ? (array) ($dto->exceptionStudents ?? [])
            : (array) ($dto->students ?? []);

        $userIdListDTO = $this->repository->getUserIdsByControlNumbers($list);

        if ($exceptions && empty($userIdListDTO->userIds)) {
            throw new ExceptionStudentsNotFoundException();
        }

        if (!$exceptions && empty($userIdListDTO->userIds)) {
            throw new StudentsNotFoundException();
        }
        return $userIdListDTO;
    }

    public function notifyRecipients(PaymentConcept $concept, array $recipients): void {
        if (empty($recipients)) {
            return;
        }
        $chunks = array_chunk($recipients, 100);

        foreach ($chunks as $chunk) {
            $userIds = array_map(fn($user) => $user->id, $chunk);
            ClearCacheForUsersJob::forConceptStatus($userIds, $concept->status)
                ->onQueue('cache')
                ->delay(now()->addSeconds(5));

            $mailables = [];
            $recipientEmails = [];

            foreach ($chunk as $user) {

                $data = [
                    'recipientName' => $user->fullName,
                    'recipientEmail' => $user->email,
                    'concept_name' => $concept->concept_name,
                    'amount' => $concept->amount,
                    'end_date' => $concept->end_date ? $concept->end_date->format('d-m-Y') : 'Sin fecha límite',
                    'start_date' => $concept->start_date->format('d-m-Y'),
                    'isDisable' => $concept->isDisable(),
                ];

                $mailables[] = new NewConceptMail(
                    MailMapper::toNewPaymentConceptEmailDTO($data)
                );
                $recipientEmails[] = $user->email;
            }

            SendBulkMailJob::forRecipients($mailables, $recipientEmails, 'concept_notification')
                ->onQueue('emails')
                ->delay(now()->addSeconds(5));
        }
    }
}
