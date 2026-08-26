<?php

namespace App\Core\Application\Traits;

use App\Core\Application\DTO\Request\PaymentConcept\CreatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Application\DTO\Response\User\UserRecipientDTO;
use App\Core\Application\Factories\Emails\Events\EmailEventFactory;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Utils\Helpers\EventSourceId;
use App\Exceptions\NotFound\ExceptionStudentsNotFoundException;
use App\Exceptions\NotFound\StudentsNotFoundException;
use App\Jobs\ClearCacheForUsersJob;
use App\Jobs\SendBulkMailJob;
use App\Mail\NewConceptMail;
use Illuminate\Support\Facades\Log;

trait HasPaymentConcept
{

    private UserQueryRepInterface $repository;
    private ?EmailEventManagerInterface $manager = null;
    public function setRepository(UserQueryRepInterface $repository): void
    {
        $this->repository = $repository;
    }
    public function setEmailEventManager(EmailEventManagerInterface $manager): void
    {
        $this->manager = $manager;
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

    public function notifyRecipients(PaymentConcept $concept, array $recipients, string $operationId): void {
        if ($this->emailEventManager === null) {
            throw new \LogicException(
                'EmailEventManager is required to notify payment concept recipients.'
            );
        }

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
            $eventIds = [];
            /** @var UserRecipientDTO $user */
            foreach ($chunk as $user) {
                $emailEvent = $this->manager->findOrCreate(
                    eventType: EmailEventType::CONCEPT_CREATED,
                    sourceType: EmailEventSourceType::CONCEPT,
                    sourceId: EventSourceId::email(
                        sourceType: EmailEventSourceType::CONCEPT,
                        eventType: EmailEventType::CONCEPT_CREATED,
                        operationId: $operationId,
                        recipientId: $user->id
                    ),
                    factory: fn () => EmailEventFactory::conceptCreated(
                        user: $user,
                        concept: $concept,
                        operationId: $operationId
                    ),
                );
                $eventIds[] = $emailEvent->id;
                $mailables[] = new NewConceptMail(
                    MailMapper::toNewPaymentConceptEmailDTO(user: $user, concept: $concept),
                );
                $recipientEmails[] = $user->email;
            }

            $this->manager->dispatchBulk(
                eventIds: $eventIds,
                mailables: $mailables,
                recipientEmails: $recipientEmails,
                jobType: 'concept_notification'
            );
        }
    }
}
