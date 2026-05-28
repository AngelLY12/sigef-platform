<?php

namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Application\DTO\Request\PaymentConcept\CreatePaymentConceptDTO;
use App\Core\Application\DTO\Response\PaymentConcept\CreatePaymentConceptResponse;
use App\Core\Application\Mappers\PaymentConceptMapper;
use App\Core\Application\Traits\HasPaymentConcept;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Utils\Validators\PaymentConceptValidator;
use App\Events\AdministrationEvent;
use App\Events\PaymentConceptCreated;
use App\Exceptions\NotFound\CareersNotFoundException;
use App\Exceptions\NotFound\RecipientsNotFoundException;
use App\Exceptions\NotFound\StudentsNotFoundException;
use App\Exceptions\Validation\ApplicantTagInvalidException;
use App\Exceptions\Validation\CareerSemesterInvalidException;
use App\Exceptions\Validation\SemestersNotFoundException;
use Illuminate\Support\Facades\DB;

class CreatePaymentConceptUseCase
{
    use HasPaymentConcept;

    public function __construct(
        private PaymentConceptRepInterface $pcRepo,
        private UserQueryRepInterface $uqRepo,
    )
    {
        $this->setRepository($uqRepo);
    }

    public function execute(CreatePaymentConceptDTO $dto): CreatePaymentConceptResponse {
        $this->preValidateRecipients($dto);
        $paymentConcept= DB::transaction(function() use ($dto) {
            $pc = PaymentConceptMapper::toDomain($dto);
            PaymentConceptValidator::ensureConceptHasRequiredFields($pc);

            $paymentConcept = $this->pcRepo->create($pc);
            if ($dto->exceptionStudents) {
                $userIds = $this->getUserIdListDTO($dto, true);
                $paymentConcept= $this->pcRepo->attachToExceptionStudents($paymentConcept->id, $userIds);
            }

            $paymentConcept=$this->attachAppliesTo($dto, $paymentConcept);

            $hasRecipients = $this->hasAnyRecipientFast($paymentConcept, $dto->appliesTo);
            if (!$hasRecipients) {
                throw new RecipientsNotFoundException();
            }

            return $paymentConcept;
        });
        $affectedCount = count($this->uqRepo->getRecipientsIds($paymentConcept, $dto->appliesTo->value));
        event(new PaymentConceptCreated($paymentConcept->id, $paymentConcept->applies_to->value));
        if( config('concepts.amount.notifications.enabled') && bccomp($paymentConcept->amount, config('concepts.amount.notifications.threshold')) === 1)
        {
            event(new AdministrationEvent(
                amount: $paymentConcept->amount,
                id: $paymentConcept->id,
                concept_name: $paymentConcept->concept_name,
                action: "creó",
            ));
        }
        return PaymentConceptMapper::toCreatePaymentConceptResponse($paymentConcept, $affectedCount);
    }

    private function preValidateRecipients(CreatePaymentConceptDTO $dto): void
    {
        PaymentConceptValidator::ensureCreatePaymentDTOIsValid($dto);
    }

    private function hasAnyRecipientFast(PaymentConcept $concept, PaymentConceptAppliesTo $appliesTo): bool
    {
        return $this->uqRepo->hasAnyRecipient($concept, $appliesTo->value);
    }

    private function attachAppliesTo(CreatePaymentConceptDTO $dto, PaymentConcept $paymentConcept): PaymentConcept
    {
        switch($dto->appliesTo) {
            case PaymentConceptAppliesTo::CARRERA:
                if ($dto->careers) {
                    $paymentConcept=$this->pcRepo->attachToCareer($paymentConcept->id, $dto->careers);

                } else {
                    throw new CareersNotFoundException();
                }
                break;
            case PaymentConceptAppliesTo::SEMESTRE:
                if ($dto->semesters) {
                    $paymentConcept=$this->pcRepo->attachToSemester($paymentConcept->id, $dto->semesters);
                } else {
                    throw new SemestersNotFoundException();
                }
                break;
            case PaymentConceptAppliesTo::ESTUDIANTES:
                if ($dto->students) {
                    $userIdListDTO = $this->getUserIdListDTO($dto);
                    $paymentConcept=$this->pcRepo->attachToUsers($paymentConcept->id, $userIdListDTO);
                }else{
                    throw new StudentsNotFoundException();
                }
                break;
            case PaymentConceptAppliesTo::CARRERA_SEMESTRE:
                if($dto->careers && $dto->semesters){
                    $paymentConcept = $this->pcRepo->attachToCareer($paymentConcept->id, $dto->careers);
                    $paymentConcept = $this->pcRepo->attachToSemester($paymentConcept->id, $dto->semesters);
                }else {
                    throw new CareerSemesterInvalidException();
                }
                break;
            case PaymentConceptAppliesTo::TAG:
                if($dto->applicantTags){
                    $paymentConcept= $this->pcRepo->attachToApplicantTag($paymentConcept->id, $dto->applicantTags);
                }else{
                    throw new ApplicantTagInvalidException();
                }
                break;
            case PaymentConceptAppliesTo::TODOS:
                break;
            default:
                break;
        }
        return $paymentConcept;
    }

}
