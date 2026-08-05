<?php

namespace App\Core\Domain\Enum\Payment;

enum PaymentReconcileReason: string
{
    case RECENT_BATCH = 'recent_batch';
    case MANUAL = 'manual';
    case FULL_RECOVERY = 'full_recovery';
}
