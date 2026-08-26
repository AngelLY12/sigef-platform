<?php

namespace App\Core\Domain\Utils\Validators;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Utils\Helpers\Money;
use App\Exceptions\NotAllowed\NotAllowedException;
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

    public static function ensurePaymentIsValidToReconcile(Payment $payment)
    {
        $terminalStatuses = PaymentStatus::terminalStatuses();
        if(in_array($payment->status, $terminalStatuses)) {
            throw new NotAllowedException('El pago no es apto para reconciliar');
        }
        $expected = Money::from($payment->amount);
        $received = Money::from($payment->amount_received ?? '0');

        if($received->isEqualTo($expected) || $received->isGreaterThan($expected)) {
            throw new NotAllowedException('El pago no es apto para reconciliar');
        }

        if(!$payment->payment_intent_id || !$payment->payment_method_details || !$payment->stripe_payment_method_id)
        {
            throw new NotAllowedException('El pago no es apto para reconciliar');
        }

    }

}
