<?php

namespace App\Core\Domain\Repositories\Command\Payments;

use App\Models\Receipt;

interface ReceiptRepInterface
{
    public function getOrCreateReceipt(int $paymentId): ?Receipt;
    public function findByFolio(string $folio): Receipt;

}
