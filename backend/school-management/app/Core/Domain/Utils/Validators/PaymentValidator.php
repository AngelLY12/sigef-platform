<?php

namespace App\Core\Domain\Utils\Validators;

use App\Core\Domain\Entities\Payment;
use App\Exceptions\NotAllowed\PaymentRetryNotAllowedException;

class PaymentValidator
{

    public static function ensurePaymentIsValidToRepay(Payment $payment)
    {
        if (! $payment->isRecentPayment()) {
            throw new PaymentRetryNotAllowedException("No se puede volver a pagar: el intento de pago anterior fue hace más de 1 hora.");
        }

        if (! is_null($payment->amount_received)) {
            throw new PaymentRetryNotAllowedException("No se puede volver a pagar: el pago ya recibió algún monto.");
        }

        if (! $payment->isNonPaid()) {
            throw new PaymentRetryNotAllowedException("No se puede volver a pagar: el pago actual ya está en estado terminal.");
        }
    }

}
