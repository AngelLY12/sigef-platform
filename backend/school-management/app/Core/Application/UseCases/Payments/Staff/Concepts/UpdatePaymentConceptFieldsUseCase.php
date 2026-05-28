<?php

namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptDTO;
use App\Core\Application\DTO\Response\PaymentConcept\UpdatePaymentConceptResponse;
use App\Core\Application\Mappers\PaymentConceptMapper;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Utils\Helpers\Money;
use App\Core\Domain\Utils\Validators\PaymentConceptValidator;
use App\Events\AdministrationEvent;
use App\Events\PaymentConceptUpdatedFields;
use App\Exceptions\NotFound\ConceptNotFoundException;
use App\Exceptions\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdatePaymentConceptFieldsUseCase
{
    public function __construct(
        private PaymentConceptRepInterface $pcRepo,
        private PaymentConceptQueryRepInterface $pcqRepo,
    )
    {
    }
     public function execute(UpdatePaymentConceptDTO $dto): UpdatePaymentConceptResponse {
        [$newPaymentConcept, $oldPaymentConcept]= DB::transaction(function() use ($dto) {

            $existingConcept = $this->pcqRepo->findById($dto->id);

            if (!$existingConcept) {
                throw new ConceptNotFoundException();
            }
            PaymentConceptValidator::ensureConceptIsValidToUpdate($existingConcept);
            $fieldsToUpdate=$this->validFields($dto);
            if (empty($fieldsToUpdate)) {
                throw new ValidationException('No se encontraron o proporcionaron campos para actualizar');
            }
            PaymentConceptValidator::ensureUpdatedFieldsAreValid($existingConcept, $fieldsToUpdate);

            $paymentConcept = $this->pcRepo->update($existingConcept->id, $fieldsToUpdate);
            return [$paymentConcept,$existingConcept];
        });

         return $this->formattResponse($newPaymentConcept,$oldPaymentConcept);
    }

    private function validFields(UpdatePaymentConceptDTO $dto): array
    {
        $allowedFields = ['concept_name', 'description', 'start_date', 'end_date', 'amount'];
        $fieldsToUpdate = [];

        foreach ($dto->toArray() as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                continue;
            }
            if ($value !== null && $value !== '' && $value !== []) {
                $fieldsToUpdate[$key] = $value;
            }
        }
        return $fieldsToUpdate;
    }

    private function formattResponse(PaymentConcept $newPaymentConcept, PaymentConcept $oldPaymentConcept): UpdatePaymentConceptResponse
    {
        $changes=$this->calculateChanges($oldPaymentConcept,$newPaymentConcept);
        $data=[
            'message'=>$this->generateSuccessMessage($changes),
            'changes'=>$changes,
        ];
        if ($this->shouldNotifyForChanges($changes)) {
            event(new PaymentConceptUpdatedFields($newPaymentConcept->id, $changes));
        }
        if(config('concepts.amount.notifications.enabled') && bccomp($newPaymentConcept->amount, config('concepts.amount.notifications.threshold')) === 1)
        {
            event(new AdministrationEvent(
                amount: $newPaymentConcept->amount,
                id: $newPaymentConcept->id,
                concept_name: $newPaymentConcept->concept_name,
                action: "actualizó",
            ));
        }
        return PaymentConceptMapper::toUpdatePaymentConceptResponse($newPaymentConcept, $data);
    }

    private function shouldNotifyForChanges(array $changes): bool
    {
        $importantFields = ['amount', 'description', 'concept_name', 'start_date', 'end_date'];
        foreach ($changes as $change)
        {
            if ($change['type'] === 'field_update' && in_array($change['field'], $importantFields)) {
                return true;
            }
        }
        return false;
    }

    private function calculateChanges(
        PaymentConcept $oldConcept,
        PaymentConcept $newConcept,
    ): array {
        $changes = [];

        $basicFields = ['concept_name', 'description', 'amount', 'start_date', 'end_date'];
        foreach ($basicFields as $field) {
            $oldValue = $oldConcept->$field;
            $newValue = $newConcept->$field;

            if ($this->valuesDiffer($oldValue, $newValue)) {
                $changes[] = [
                    'field' => $field,
                    'old' => $oldValue,
                    'new' => $newValue,
                    'type' => 'field_update'
                ];
            }
        }
        return $changes;
    }

    private function valuesDiffer($oldValue, $newValue): bool
    {
        if ($oldValue instanceof Carbon && $newValue instanceof Carbon) {
            return !$oldValue->eq($newValue);
        }

        if (is_float($oldValue) || is_float($newValue)) {
            return bccomp((string)$oldValue, (string)$newValue, 2) !== 0;
        }

        return $oldValue != $newValue;
    }

    private function formatValueForDisplay($value, string $field): string
    {
        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        if ($field === 'amount' && is_numeric($value)) {
            return '$' . Money::from((string) $value)->finalize();
        }

        return (string)$value;
    }
    private function getFieldDisplayName(string $field): string
    {
        return match($field) {
            'concept_name' => 'Nombre',
            'description' => 'Descripción',
            'amount' => 'Monto',
            'start_date' => 'Fecha inicio',
            'end_date' => 'Fecha fin',
            default => $field
        };
    }

    private function generateSuccessMessage(array $changes): string
    {
        if (empty($changes)) {
            return 'Concepto actualizado sin cambios';
        }
        $messages = [];
        foreach ($changes as $change)
        {
            $fieldName = $this->getFieldDisplayName($change['field']);
            $oldFormatted = $this->formatValueForDisplay($change['old'], $change['field']);
            $newFormatted = $this->formatValueForDisplay($change['new'], $change['field']);

            $messages[] = "$fieldName: $oldFormatted → $newFormatted";
        }


        return 'Concepto actualizado: ' . implode('. ', $messages);
    }

}
