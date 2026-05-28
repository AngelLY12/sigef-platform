<?php

namespace App\Core\Application\UseCases\Payments\Staff\Concepts;

use App\Core\Application\DTO\Response\PaymentConcept\ConceptChangeStatusResponse;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Utils\Validators\PaymentConceptValidator;

class FinalizePaymentConceptUseCase extends BasePaymentConceptStatusUseCase
{
    protected function getTargetStatus(): PaymentConceptStatus
    {
        return PaymentConceptStatus::FINALIZADO;
    }

    protected function getRepositoryMethod(): string
    {
        return 'finalize';
    }
    public function execute(PaymentConcept $concept): ConceptChangeStatusResponse
    {
        PaymentConceptValidator::ensureConceptHasStarted($concept);
        return parent::execute($concept);
    }
    protected function getSuccessMessage(): string
    {
        return 'El concepto fue finalizado correctamente';
    }
}
