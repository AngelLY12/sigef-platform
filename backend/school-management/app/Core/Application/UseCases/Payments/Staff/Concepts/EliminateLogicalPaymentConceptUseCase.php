<?php
namespace App\Core\Application\UseCases\Payments\Staff\Concepts;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;


class EliminateLogicalPaymentConceptUseCase extends BasePaymentConceptStatusUseCase
{
    protected function getTargetStatus(): PaymentConceptStatus
    {
        return PaymentConceptStatus::ELIMINADO;
    }

    protected function getRepositoryMethod(): string
    {
        return 'deleteLogical';
    }
    protected function getSuccessMessage(): string
    {
        return 'El concepto fue eliminado correctamente';
    }
}
