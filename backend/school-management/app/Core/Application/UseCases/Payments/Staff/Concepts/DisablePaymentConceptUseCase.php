<?php
namespace App\Core\Application\UseCases\Payments\Staff\Concepts;


use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;



class DisablePaymentConceptUseCase extends BasePaymentConceptStatusUseCase
{

    protected function getTargetStatus(): PaymentConceptStatus
    {
        return PaymentConceptStatus::DESACTIVADO;
    }

    protected function getRepositoryMethod(): string
    {
        return 'disable';
    }
    protected function getSuccessMessage(): string
    {
        return 'El concepto fue desactivado correctamente';
    }

}
