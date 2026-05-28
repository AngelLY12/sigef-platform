<?php

namespace App\Core\Application\UseCases\Payments\Student\PaymentHistory;

use App\Core\Application\Services\Payments\Student\ReceiptService;
use App\Core\Domain\Repositories\Command\Payments\ReceiptRepInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GenerateReceiptFromPaymentUseCase
{

    public function __construct(
        private ReceiptRepInterface $receiptRep,
        private ReceiptService $receiptPdfService
    ){}

    public function execute(int $paymentId): string
    {
        $receipt = $this->receiptRep->getOrCreateReceipt($paymentId);
        if(!$receipt)
        {
            throw new ModelNotFoundException('No se encontro el recibo solicitado');
        }
        $path =$this->receiptPdfService->generate($receipt);
        return $path;
    }

}
