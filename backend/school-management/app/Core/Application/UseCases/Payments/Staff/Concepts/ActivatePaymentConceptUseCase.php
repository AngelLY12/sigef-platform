<?php
namespace App\Core\Application\UseCases\Payments\Staff\Concepts;



use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;

class ActivatePaymentConceptUseCase extends BasePaymentConceptStatusUseCase
{
    protected function getTargetStatus(): PaymentConceptStatus
    {
        return PaymentConceptStatus::ACTIVO;
    }

    protected function getRepositoryMethod(): string
    {
        return 'activate';
    }
    protected function getSuccessMessage(): string
    {
        return 'El concepto fue activado correctamente';
    }
}
