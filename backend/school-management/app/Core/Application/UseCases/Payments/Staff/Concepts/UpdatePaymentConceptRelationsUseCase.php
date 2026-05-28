<?php

namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Application\DTO\Response\PaymentConcept\UpdatePaymentConceptRelationsResponse;
use App\Core\Application\Mappers\PaymentConceptMapper;
use App\Core\Application\Traits\HasPaymentConcept;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Utils\Validators\PaymentConceptValidator;
use App\Events\PaymentConceptUpdatedRelations;
use App\Exceptions\NotFound\CareersNotFoundException;
use App\Exceptions\NotFound\ConceptNotFoundException;
use App\Exceptions\NotFound\RecipientsNotFoundException;
use App\Exceptions\NotFound\StudentsNotFoundException;
use App\Exceptions\Validation\ApplicantTagInvalidException;
use App\Exceptions\Validation\CareerSemesterInvalidException;
use App\Exceptions\Validation\SemestersNotFoundException;
use Illuminate\Support\Facades\DB;

class UpdatePaymentConceptRelationsUseCase
{
    use HasPaymentConcept;
    public function __construct(
        private PaymentConceptRepInterface $pcRepo,
        private PaymentConceptQueryRepInterface $pcqRepo,
        private UserQueryRepInterface $uqRepo,
    )
    {
        $this->setRepository($uqRepo);
    }
    public function execute(UpdatePaymentConceptRelationsDTO $dto): UpdatePaymentConceptRelationsResponse {
        $this->preValidateUpdate($dto);
        [$newPaymentConcept, $oldPaymentConcept, $oldRecipientIds]= DB::transaction(function() use ($dto) {

            $existingConcept = $this->pcqRepo->findById($dto->id);

            if (!$existingConcept) {
                throw new ConceptNotFoundException();
            }
            $oldRecipientIds=$this->uqRepo->getRecipientsIds($existingConcept,$existingConcept->applies_to->value);
            PaymentConceptValidator::ensureConsistencyAppliesToToUpdate($dto,$existingConcept);

            $fieldsToUpdate = $this->validFields($dto);
            $paymentConcept = !empty($fieldsToUpdate)
                ? $this->pcRepo->update($existingConcept->id, $fieldsToUpdate)
                : $existingConcept;
            if($dto->removeAllExceptions)
            {
                $this->pcRepo->detachFromExceptionStudents($paymentConcept->id);
            }

            if ($dto->exceptionStudents) {
                $userIdListDTO = $this->getUserIdListDTO($dto, true);
                $paymentConcept = $this->pcRepo->attachToExceptionStudents(
                    $paymentConcept->id,
                    $userIdListDTO,
                    $dto->replaceExceptions
                );
            }

            $shouldUpdateRelations = $dto->careers !== null
                || $dto->semesters !== null
                || $dto->students !== null
                || $dto->applicantTags !== null;

            if($dto->appliesTo || $shouldUpdateRelations){
                $paymentConcept=$this->attachAppliesTo($dto,$paymentConcept);
                $hasRecipients = $this->uqRepo->hasAnyRecipient($paymentConcept, $paymentConcept->applies_to->value);

                if (!$hasRecipients) {
                    throw new RecipientsNotFoundException();
                }

            }

            return [$paymentConcept,$existingConcept, $oldRecipientIds];
        });

        event(new PaymentConceptUpdatedRelations(
            $newPaymentConcept->id,
            $oldPaymentConcept->toArray(),
            $dto->toArrayEntire(),
            $newPaymentConcept->applies_to->value,
            $oldRecipientIds,
        ));

        return $this->formattResponse($newPaymentConcept,$oldPaymentConcept,$oldRecipientIds);
    }

    private function validFields(UpdatePaymentConceptRelationsDTO $dto): array
    {
        $allowedFields = ['applies_to'];
        $fieldsToUpdate = [];

        foreach ($dto->toArray() as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                continue;
            }
            if ($value !== null) {
                $fieldsToUpdate[$key] = $value;
            }
        }
        return $fieldsToUpdate;
    }

    private function formattResponse(PaymentConcept $newPaymentConcept, PaymentConcept $oldPaymentConcept, array $oldRecipientIds): UpdatePaymentConceptRelationsResponse
    {
        $newRecipientIds = $this->uqRepo->getRecipientsIds($newPaymentConcept, $newPaymentConcept->applies_to->value);
        $oldAffectedCount= count($oldRecipientIds);
        $changes=$this->calculateChanges($oldPaymentConcept,$newPaymentConcept);
        $newlyAddedIds = array_diff($newRecipientIds, $oldRecipientIds);
        $removedIds = array_diff($oldRecipientIds, $newRecipientIds);
        $keptIds = array_intersect($oldRecipientIds, $newRecipientIds);
        $data=[
            'message'=>$this->generateSuccessMessage($changes),
            'changes'=>$changes,
            'affectedSummary' =>[
                'newlyAffectedCount'=>count($newlyAddedIds),
                'removedCount' => count($removedIds),
                'keptCount' => count($keptIds),
                'totalAffectedCount' => count($newlyAddedIds) + count($removedIds),
                'previouslyAffectedCount' => $oldAffectedCount
            ],
        ];

        return PaymentConceptMapper::toUpdatePaymentConceptRelationsResponse($newPaymentConcept, $data);
    }


    private function calculateChanges(
        PaymentConcept $oldConcept,
        PaymentConcept $newConcept,
    ): array {
        $changes = [];

        if ($oldConcept->applies_to !== $newConcept->applies_to) {
            $changes[] = [
                'field' => 'applies_to',
                'old' => $oldConcept->applies_to->value,
                'new' => $newConcept->applies_to->value,
                'type' => 'applies_to_changed',
            ];
        }
        $relations = ['careers', 'semesters', 'students', 'applicant_tags'];
        foreach ($relations as $relation) {
            $oldIds = $this->getRelationIds($oldConcept, $relation);
            $newIds = $this->getRelationIds($newConcept, $relation);

            if ($oldIds != $newIds) {
                $added = array_diff($newIds, $oldIds);
                $removed = array_diff($oldIds, $newIds);

                $changes[] = [
                    'field' => $relation,
                    'added' => array_values($added),
                    'removed' => array_values($removed),
                    'type' => 'relation_update'
                ];
            }
        }
        $oldExceptions = $oldConcept->getExceptionUsersIds();
        $newExceptions = $newConcept->getExceptionUsersIds();

        if ($oldExceptions != $newExceptions) {
            $removedExceptions = array_diff($oldExceptions, $newExceptions);

            $changes[] = [
                'field' => 'exceptions',
                'added' => array_diff($newExceptions, $oldExceptions),
                'removed' => array_values($removedExceptions),
                'type' => 'exceptions_update',
                'note' => !empty($removedExceptions) && $newConcept->applies_to === PaymentConceptAppliesTo::ESTUDIANTES
                    ? 'Las excepciones fueron eliminadas automáticamente al cambiar a "aplica a estudiantes"'
                    : null
            ];
        }

        return $changes;
    }

    private function generateSuccessMessage(array $changes): string
    {
        if (empty($changes)) {
            return 'Concepto actualizado sin cambios en destinatarios';
        }

        $significantChanges = array_filter($changes, fn($c) => in_array($c['type'], [
            'applies_to_changed', 'relation_update', 'exceptions_update'
        ]));

        if (empty($significantChanges)) {
            return 'Concepto actualizado exitosamente';
        }

        $messages = [];
        foreach ($significantChanges as $change) {
            switch ($change['type']) {
                case 'applies_to_changed':
                    $messages[] = "Ahora aplica a {$change['new']}";
                    break;
                case 'relation_update':
                    if (!empty($change['added'])) {
                        $messages[] = "Se agregaron " . count($change['added']) . " {$change['field']}";
                    }
                    if (!empty($change['removed'])) {
                        $messages[] = "Se removieron " . count($change['removed']) . " {$change['field']}";
                    }
                    break;
                case 'exceptions_update':
                    if (!empty($change['note'])) {
                        $messages[] = $change['note'];
                    }
                    break;
            }
        }

        return 'Concepto actualizado: ' . implode('. ', $messages);
    }

    private function getRelationIds(PaymentConcept $concept, string $relation): array
    {
        return match($relation) {
            'careers' => $concept->getCareerIds(),
            'semesters' => $concept->getSemesters(),
            'students' => $concept->applies_to === PaymentConceptAppliesTo::ESTUDIANTES
                ? $concept->getUserIds()
                : [],
            'applicant_tags' =>  array_map(
                fn($tag) => is_string($tag) ? $tag : $tag->value,
                $concept->getApplicantTag()
            ),
            default => []
        };
    }

    private function preValidateUpdate(UpdatePaymentConceptRelationsDTO $dto): void
    {
        PaymentConceptValidator::ensureUpdatePaymentConceptDTOIsValid($dto);
    }

    private function attachAppliesTo(UpdatePaymentConceptRelationsDTO $dto,PaymentConcept $paymentConcept): PaymentConcept
    {
        $detachFlags = $this->determineDetachFlags($paymentConcept->applies_to);

        switch($paymentConcept->applies_to) {
            case PaymentConceptAppliesTo::CARRERA:
                if ($dto->careers) {
                    $paymentConcept=$this->pcRepo->attachToCareer($paymentConcept->id, $dto->careers,$dto->replaceRelations);
                } else {
                    throw new CareersNotFoundException();
                }
                break;
            case PaymentConceptAppliesTo::SEMESTRE:

                if ($dto->semesters) {
                    $paymentConcept=$this->pcRepo->attachToSemester($paymentConcept->id, $dto->semesters, $dto->replaceRelations);
                }else{
                    throw new SemestersNotFoundException();
                }
                break;
            case PaymentConceptAppliesTo::ESTUDIANTES:
                if ($dto->students) {
                    $userIdListDTO = $this->getUserIdListDTO($dto);
                    $paymentConcept=$this->pcRepo->attachToUsers($paymentConcept->id, $userIdListDTO, $dto->replaceRelations);

                }else{
                    throw new StudentsNotFoundException();
                }
                break;
            case PaymentConceptAppliesTo::CARRERA_SEMESTRE:
                if($dto->careers && $dto->semesters){
                    $paymentConcept = $this->pcRepo->attachToCareer($paymentConcept->id, $dto->careers, $dto->replaceRelations);
                    $paymentConcept = $this->pcRepo->attachToSemester($paymentConcept->id, $dto->semesters, $dto->replaceRelations);
                }else {
                    throw new CareerSemesterInvalidException();
                }
                break;
            case PaymentConceptAppliesTo::TODOS:
                break;
            case PaymentConceptAppliesTo::TAG:
                if($dto->applicantTags)
                {
                    $paymentConcept = $this->pcRepo->attachToApplicantTag($paymentConcept->id, $dto->applicantTags ,$dto->replaceRelations);
                }else
                {
                    throw new ApplicantTagInvalidException();
                }
        }
        $this->applyDetachments($paymentConcept->id, $detachFlags);

        return $paymentConcept;

    }

    private function determineDetachFlags(PaymentConceptAppliesTo $appliesTo): array
    {
        return match($appliesTo) {
            PaymentConceptAppliesTo::CARRERA => [
                'career' => false,
                'semester' => true,
                'users' => true,
                'tags' => true,
            ],
            PaymentConceptAppliesTo::SEMESTRE => [
                'career' => true,
                'semester' => false,
                'users' => true,
                'tags' => true
            ],
            PaymentConceptAppliesTo::ESTUDIANTES => [
                'career' => true,
                'semester' => true,
                'users' => false,
                'tags' => true,
                'exceptions' => true,
            ],
            PaymentConceptAppliesTo::CARRERA_SEMESTRE => [
                'career' => false,
                'semester' => false,
                'users' => true,
                'tags' => true
            ],
            PaymentConceptAppliesTo::TAG => [
                'career' => true,
                'semester' => true,
                'users' => true,
                'tags' => false
            ],
            PaymentConceptAppliesTo::TODOS => [
                'career' => true,
                'semester' => true,
                'users' => true,
                'tags' => true
            ]
        };
    }

    private function applyDetachments(int $conceptId, array $detachFlags): void
    {
        if ($detachFlags['career'] ?? false) {
            $this->pcRepo->detachFromCareer($conceptId);
        }
        if ($detachFlags['semester'] ?? false) {
            $this->pcRepo->detachFromSemester($conceptId);
        }
        if ($detachFlags['users'] ?? false) {
            $this->pcRepo->detachFromUsers($conceptId);
        }
        if ($detachFlags['tags'] ?? false) {
            $this->pcRepo->detachFromApplicantTag($conceptId);
        }
        if($detachFlags['exceptions'] ?? false){
            $this->pcRepo->detachFromExceptionStudents($conceptId);
        }
    }
}
