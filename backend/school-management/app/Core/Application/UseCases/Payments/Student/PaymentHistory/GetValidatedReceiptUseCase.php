<?php

namespace App\Core\Application\UseCases\Payments\Student\PaymentHistory;

use App\Core\Domain\Repositories\Command\Payments\ReceiptRepInterface;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentReceiptRepository;
use App\Models\Receipt;

class GetValidatedReceiptUseCase
{
    public function __construct(
        private ReceiptRepInterface $receiptRepository,
    ){}

    public function execute(string $folio): Receipt
    {
        return $this->receiptRepository->findByFolio($folio);
    }

}
