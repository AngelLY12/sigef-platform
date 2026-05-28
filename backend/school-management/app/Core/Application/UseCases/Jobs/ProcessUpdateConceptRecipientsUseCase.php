<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Application\Traits\HasPaymentConcept;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Jobs\SendConceptUpdatedRelationsNotificationJob;
use Illuminate\Support\Facades\Log;

class ProcessUpdateConceptRecipientsUseCase
{

    use HasPaymentConcept;

    public function __construct(
        private UserQueryRepInterface $uqRepo,
    )
    {
        $this->setRepository($uqRepo);
    }

    public function execute(PaymentConcept $newPaymentConcept, PaymentConcept $oldPaymentConcept, array $oldRecipientIds ,UpdatePaymentConceptRelationsDTO $dto ,string $appliesTo): void
    {
        $notificationData=$this->getNotificationData($newPaymentConcept,$oldPaymentConcept);
        $notificationDecision= $this->shouldSendNotification($newPaymentConcept, $oldRecipientIds,$notificationData, $dto);
        $recipients=[];

        if (!$notificationDecision['should']) {
            Log::info('No notification needed for concept update', [
                'concept_id' => $newPaymentConcept->id
            ]);
            return;
        }

        if(in_array('email', $notificationDecision['notification_type'])) {
            if(empty($notificationDecision['newUserIds']))
            {
                $recipients = $this->uqRepo->getRecipients($newPaymentConcept, $appliesTo);
            }else
            {
                $recipients=$this->uqRepo->getRecipientsFromIds($notificationDecision['newUserIds']);
            }
            if(empty($recipients)){
                Log::warning('Payment concept created but no recipients found for notifications', [
                    'concept_id' => $newPaymentConcept->id,
                    'applies_to' => $appliesTo
                ]);
                return;
            }
            $this->notifyRecipients($newPaymentConcept,$recipients);

            Log::info('Payment concept update notifications sent', [
                'concept_id' =>$newPaymentConcept->id,
                'reason' => $notificationDecision['reason'],
                'recipient_count' => count($recipients),
                'applies_to' => $appliesTo
            ]);
        }



        if (in_array('broadcast', $notificationDecision['notification_type']) && !empty($notificationDecision['newUserIds'])) {
            $this->sendBroadcastForNewUserIds($newPaymentConcept, $notificationDecision);
            Log::info('Payment concept broadcast notifications sent', [
                'concept_id' => $newPaymentConcept->id,
                'reason' => $notificationDecision['reason'],
                'recipient_count' => count($notificationDecision['newUserIds']),
                'applies_to' => $newPaymentConcept->applies_to->value
            ]);
        }
        if(in_array('broadcast', $notificationDecision['notification_type']) && empty($notificationDecision['newUserIds']))
        {
            $userIds=[];
            foreach ($recipients as $recipient)
            {
                $userIds[]=$recipient->id;
            }
            $this->sendBroadcasteForAppliesChanged($newPaymentConcept, $oldPaymentConcept,$userIds, $notificationDecision);
        }
    }

    private function sendBroadcasteForAppliesChanged(PaymentConcept $newConcept, PaymentConcept $oldConcept ,array $userIds, array $notificationDecision): void
    {
        $changes=[];
        switch ($notificationDecision['reason'])
        {
            case 'applies_to_changed':
                $changes = [
                    [
                        'field' =>'applies_to',
                        'type' => 'applies_to_changed',
                        'old' => $oldConcept->applies_to->value,
                        'new' => $newConcept->applies_to->value,
                    ]
                ];
                break;
        }
        SendConceptUpdatedRelationsNotificationJob::forStudents(
            $userIds,
            $newConcept->id,
            $changes
        )
            ->onQueue('default')
            ->delay(now()->addSeconds(5));
    }

    private function sendBroadcastForNewUserIds(PaymentConcept $concept, array $notificationDecision): void
    {
        $changes = [];

        $isRemoved = str_contains($notificationDecision['reason'], '_removed');

        if ($isRemoved) {
            $field = str_replace('_removed', '', $notificationDecision['reason']);
            $fieldMap = [
                'careers' => 'careers',
                'semesters' => 'semesters',
                'students' => 'students',
                'tags' => 'applicant_tags',
                'career_semester' => 'career_semester',
                'careers_updated_in_career_semester' => 'careers_in_career_semester',
                'semesters_updated_in_career_semester' => 'semesters_in_career_semester',
                'both_career_semester_updated' => 'career_semester',
                'careers_updated_in_career_semester_removed' => 'careers_in_career_semester',
                'semesters_updated_in_career_semester_removed' => 'semesters_in_career_semester',
                'both_career_semester_updated_removed' => 'career_semester'
            ];

            $fieldName = $fieldMap[$field] ?? $field;

            $changes = [
                [
                    'type' => 'relation_removed',
                    'field' => $fieldName,
                    'added' => [],
                    'removed' => $notificationDecision['removedUserIds']
                ]
            ];
        } else {
            switch ($notificationDecision['reason'])
            {
                case 'exceptions_removed':
                    $changes = [
                        [
                            'type' => 'exceptions_update',
                            'field' => 'exceptions',
                            'added' => [],
                            'removed' => $notificationDecision['newUserIds']
                        ]
                    ];
                    break;
                case 'exceptions_added':
                    $changes = [
                        [
                            'type' => 'exceptions_update',
                            'field' => 'exceptions',
                            'added' => $notificationDecision['newUserIds'],
                            'removed' => []
                        ]
                    ];
                    break;
                case 'exceptions_replaced':
                    $changes = [
                        [
                            'type' => 'exceptions_update',
                            'field' => 'exceptions',
                            'added' => $notificationDecision['newUserIds'],
                            'removed' => $notificationDecision['removedUserIds']
                        ]
                    ];
                    break;
                case 'careers_updated':
                    $changes = [
                        [
                            'type' => 'relation_update',
                            'field' => 'careers',
                            'added' => $notificationDecision['newUserIds'],
                            'removed' => []
                        ]
                    ];
                    break;
                case 'semesters_updated':
                    $changes = [
                        [
                            'type' => 'relation_update',
                            'field' => 'semesters',
                            'added' => $notificationDecision['newUserIds'],
                            'removed' => []
                        ]
                    ];
                    break;
                case 'students_updated':
                    $changes = [
                        [
                            'type' => 'relation_update',
                            'field' => 'students',
                            'added' => $notificationDecision['newUserIds'],
                            'removed' => []
                        ]
                    ];
                    break;
                case 'tags_updated':
                    $changes = [
                        [
                            'type' => 'relation_update',
                            'field' => 'applicant_tags',
                            'added' => $notificationDecision['newUserIds'],
                            'removed' => []
                        ]
                    ];
                    break;
                case 'career_semester_updated':
                case 'both_career_semester_updated':
                    $changes = [
                        [
                            'type' => 'relation_update',
                            'field' => 'career_semester',
                            'added' => $notificationDecision['newUserIds'],
                            'removed' => []
                        ]
                    ];
                    break;

                case 'careers_updated_in_career_semester':
                    $changes = [
                        [
                            'type' => 'relation_update',
                            'field' => 'careers_in_career_semester',
                            'added' => $notificationDecision['newUserIds'],
                            'removed' => []
                        ]
                    ];
                    break;

                case 'semesters_updated_in_career_semester':
                    $changes = [
                        [
                            'type' => 'relation_update',
                            'field' => 'semesters_in_career_semester',
                            'added' => $notificationDecision['newUserIds'],
                            'removed' => []
                        ]
                    ];
                    break;

            }
        }
        $userIdsToNotify = !empty($notificationDecision['newUserIds'])
            ? $notificationDecision['newUserIds']
            : $notificationDecision['removedUserIds'];

        SendConceptUpdatedRelationsNotificationJob::forStudents(
            $userIdsToNotify,
            $concept->id,
            $changes
        )
            ->onQueue('default')
            ->delay(now()->addSeconds(5));
    }
    private function shouldSendNotification(PaymentConcept $newPaymentConcept,array $oldRecipientIds,array $notificationData, UpdatePaymentConceptRelationsDTO $dto): array
    {
        $oldAppliesTo = $notificationData['old_applies_to'];
        $newAppliesTo = $notificationData['new_applies_to'];

        if ($dto->appliesTo && $oldAppliesTo !== $newAppliesTo) {

            if ($oldAppliesTo === PaymentConceptAppliesTo::TODOS || $newAppliesTo === PaymentConceptAppliesTo::TODOS) {
                return [
                    'should' => false,
                    'newUserIds' => [],
                    'removedUserIds' => [],
                    'reason' => 'changes_involving_todos',
                    'notification_type' => [],
                    'applies_to' => $newAppliesTo->value
                ];
            }

            return [
                'should' => true,
                'newUserIds' => [],
                'removedUserIds' => [],
                'reason' => 'applies_to_changed',
                'notification_type' => ['email', 'broadcast'],
                'applies_to' => $newAppliesTo->value
            ];
        }

        if ($dto->removeAllExceptions && !empty($notificationData['old_exception_ids'])) {
            return [
                'should' => true,
                'newUserIds' => $notificationData['old_exception_ids'],
                'removedUserIds' => [],
                'reason' => 'exceptions_removed',
                'notification_type' => ['email', 'broadcast'],
                'applies_to' => $newAppliesTo->value
            ];
        }
        if ($dto->exceptionStudents) {
            $oldExceptionIds = $notificationData['old_exception_ids'];
            $newExceptionIds = $notificationData['new_exception_ids'];

            $addedToExceptions = array_diff($newExceptionIds, $oldExceptionIds);
            $removedFromExceptions = array_diff($oldExceptionIds, $newExceptionIds);

            $hasAdded = !empty($addedToExceptions);
            $hasRemoved = !empty($removedFromExceptions);

            if ($hasAdded && !$hasRemoved) {
                return [
                    'should' => true,
                    'newUserIds' => $addedToExceptions,
                    'removedUserIds' => [],
                    'reason' => 'exceptions_added',
                    'notification_type' => ['broadcast'],
                    'applies_to' => $newAppliesTo->value
                ];
            }

            if (!$hasAdded && $hasRemoved && $dto->replaceExceptions) {
                return [
                    'should' => true,
                    'newUserIds' => [],
                    'removedUserIds' => $removedFromExceptions,
                    'reason' => 'exceptions_removed',
                    'notification_type' => ['email', 'broadcast'],
                    'applies_to' => $newAppliesTo->value
                ];
            }

            if ($hasAdded && $hasRemoved && $dto->replaceExceptions) {
                return [
                    'should' => true,
                    'newUserIds' => $addedToExceptions,
                    'removedUserIds' => $removedFromExceptions,
                    'reason' => 'exceptions_replaced',
                    'notification_type' => ['broadcast'],
                    'applies_to' => $newAppliesTo->value
                ];
            }
        }

        if (!$dto->appliesTo && $oldAppliesTo === $newAppliesTo)
        {
            if ($oldAppliesTo === PaymentConceptAppliesTo::TODOS) {
                return [
                    'should' => false,
                    'newUserIds' => [],
                    'removedUserIds' => [],
                    'reason' => 'todos_no_relations',
                    'notification_type' => [],
                    'applies_to' => $newAppliesTo->value
                ];
            }

            $newRecipientIds = $this->uqRepo->getRecipientsIds($newPaymentConcept, $newAppliesTo->value);
            $newlyAddedIds = array_diff($newRecipientIds, $oldRecipientIds);
            $removedIds = array_diff($oldRecipientIds, $newRecipientIds);

            if (!empty($newlyAddedIds)) {
                $reason = $this->determineRelationChangeReason($oldAppliesTo, $dto, true);

                if ($oldAppliesTo === PaymentConceptAppliesTo::CARRERA_SEMESTRE) {
                    if ($dto->careers && !$dto->semesters) {
                        $reason = 'careers_updated_in_career_semester';
                    } elseif (!$dto->careers && $dto->semesters) {
                        $reason = 'semesters_updated_in_career_semester';
                    } elseif ($dto->careers && $dto->semesters) {
                        $reason = 'both_career_semester_updated';
                    }
                }
                return [
                    'should' => true,
                    'newUserIds' => $newlyAddedIds,
                    'removedUserIds' => [],
                    'reason' => $reason,
                    'notification_type' => ['email', 'broadcast'],
                    'applies_to' => $newAppliesTo->value
                ];
            }
            if (!empty($removedIds) && $this->hasReplaceRelations($oldAppliesTo, $dto)) {
                $reason = $this->determineRelationChangeReason($oldAppliesTo, $dto, false);

                return [
                    'should' => true,
                    'newUserIds' => [],
                    'removedUserIds' => $removedIds,
                    'reason' => $reason . '_removed',
                    'notification_type' => ['broadcast'],
                    'applies_to' => $newAppliesTo->value
                ];
            }
        }

        return [
            'should' => false,
            'newUserIds' => [],
            'removedUserIds' => [],
            'reason' => '',
            'notification_type' => [],
            'applies_to' => $newAppliesTo->value
        ];
    }

    private function determineRelationChangeReason(PaymentConceptAppliesTo $oldAppliesTo, UpdatePaymentConceptRelationsDTO $dto, bool $isAdded = true): string
    {
        if ($oldAppliesTo === PaymentConceptAppliesTo::TODOS) {
            return 'todos_no_changes';
        }

        if ($oldAppliesTo === PaymentConceptAppliesTo::CARRERA && $dto->careers) {
            return $isAdded ? 'careers_updated' : 'careers_removed';
        }

        if ($oldAppliesTo === PaymentConceptAppliesTo::SEMESTRE && $dto->semesters) {
            return $isAdded ? 'semesters_updated' : 'semesters_removed';
        }

        if ($oldAppliesTo === PaymentConceptAppliesTo::ESTUDIANTES && $dto->students) {
            return $isAdded ? 'students_updated' : 'students_removed';
        }

        if ($oldAppliesTo === PaymentConceptAppliesTo::TAG && $dto->applicantTags) {
            return $isAdded ? 'tags_updated' : 'tags_removed';
        }

        if ($oldAppliesTo === PaymentConceptAppliesTo::CARRERA_SEMESTRE && ($dto->careers || $dto->semesters)) {
            if (!$isAdded) {
                if ($dto->careers && !$dto->semesters) {
                    return 'careers_updated_in_career_semester_removed';
                } elseif (!$dto->careers && $dto->semesters) {
                    return 'semesters_updated_in_career_semester_removed';
                } elseif ($dto->careers && $dto->semesters) {
                    return 'both_career_semester_updated_removed';
                }
            }
            return $isAdded ? 'career_semester_updated' : 'career_semester_removed';
        }

        return $isAdded ? 'relations_updated' : 'relations_removed';
    }

    private function hasReplaceRelations(PaymentConceptAppliesTo $appliesTo, UpdatePaymentConceptRelationsDTO $dto): bool
    {
        switch ($appliesTo) {
            case PaymentConceptAppliesTo::CARRERA:
                return $dto->replaceRelations === true && $dto->careers === true;

            case PaymentConceptAppliesTo::SEMESTRE:
                return $dto->replaceRelations === true && $dto->semesters === true;

            case PaymentConceptAppliesTo::ESTUDIANTES:
                return $dto->replaceRelations === true && $dto->students === true;

            case PaymentConceptAppliesTo::TAG:
                return $dto->replaceRelations === true && $dto->applicantTags === true;

            case PaymentConceptAppliesTo::CARRERA_SEMESTRE:
                return $dto->replaceRelations === true && ($dto->careers === true || $dto->semesters === true);

            default:
                return false;
        }
    }

    private function getNotificationData(PaymentConcept $newPaymentConcept, PaymentConcept $oldPaymentConcept): array
    {
        return [
            'old_applies_to' => $oldPaymentConcept->applies_to,
            'new_applies_to' => $newPaymentConcept->applies_to,
            'old_exception_ids' => $oldPaymentConcept->getExceptionUsersIds(),
            'new_exception_ids' => $newPaymentConcept->getExceptionUsersIds(),
            'old_career_ids' => $oldPaymentConcept->getCareerIds(),
            'new_career_ids' => $newPaymentConcept->getCareerIds(),
            'old_semesters' => $oldPaymentConcept->getSemesters(),
            'new_semesters' => $newPaymentConcept->getSemesters(),
            'old_user_ids' => $oldPaymentConcept->getUserIds(),
            'new_user_ids' => $newPaymentConcept->getUserIds(),
            'old_applicant_tags' => $oldPaymentConcept->getApplicantTag(),
            'new_applicant_tags' => $newPaymentConcept->getApplicantTag(),
        ];
    }
}
