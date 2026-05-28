<?php

namespace App\Core\Application\Traits;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Utils\Helpers\Money;

trait HasPaymentStripe
{

    private PaymentRepInterface $repo;
    public function setRepository(PaymentRepInterface $repo): void
    {
        $this->repo = $repo;
    }
    public function updatePaymentWithStripeData(Payment $payment, $pi, $charge, ?PaymentMethod $savedPaymentMethod): Payment
    {
        $expected = Money::from($payment->amount);
        $received = Money::from($payment->amount_received ?? '0.00');
        $internalStatus = $this->verifyStatus($pi, $received, $expected);
        $paymentMethodDetails = $this->formatPaymentMethodDetails($charge->payment_method_details);
        $fields=[
            'payment_method_id' => $savedPaymentMethod?->id,
            'stripe_payment_method_id' => $charge?->payment_method,
            'status' => $internalStatus,
            'payment_method_details'=>$paymentMethodDetails,
            'url' => $charge?->receipt_url ?? $payment->url,
        ];
        $fields = array_filter($fields, fn($value) => !is_null($value));
        $newPayment=$this->repo->update($payment->id, $fields);
        logger()->info("Pago {$payment->id} actualizado correctamente.");
        return $newPayment;
    }
    public function formatPaymentMethodDetails($details): array
    {
        if (!$details) {
            return [];
        }

        $type = $details->type ?? null;

        if ($type === 'card' && isset($details->card)) {
            return [
                'type' => 'tarjeta',
                'brand' => $details->card->brand,
                'last4' => $details->card->last4,
                'funding' => $details->card->funding,
            ];
        }

        if ($type === 'oxxo' && isset($details->oxxo)) {
            return [
                'type' => 'oxxo',
                'reference' => $details->oxxo->number ?? null,
                'expires_after' => $details->oxxo->expires_after ?? null,
            ];
        }

        if ($type === 'customer_balance') {
            if(isset($details->customer_balance))
            {
                $bank = $details->customer_balance->bank_transfer ?? null;

                if ($bank && ($bank->type ?? null) === 'mx_bank_transfer') {
                    return [
                        'type' => 'spei',
                        'bank_name' => $bank->bank_name ?? null,
                        'clabe' => $bank->clabe ?? null,
                        'reference' => $bank->reference ?? null,
                    ];
                }
            }
            return [
                'type' => 'spei',
            ];
        }

        return [
            'type' => $type,
        ];
    }

    public function verifyStatus($pi, Money $received, Money $expected): PaymentStatus
    {
        if ($pi->status === 'succeeded') {
            if ($received->isLessThan($expected)) {
                return PaymentStatus::UNDERPAID;
            } elseif ($received->isGreaterThan($expected)) {
                return PaymentStatus::OVERPAID;
            } else {
                return PaymentStatus::SUCCEEDED;
            }
        }

        return match($pi->status) {
            'requires_action' => PaymentStatus::REQUIRES_ACTION,
            'requires_payment_method' => PaymentStatus::UNPAID,
            'processing' => PaymentStatus::DEFAULT,
            default => PaymentStatus::DEFAULT
        };

    }
}
