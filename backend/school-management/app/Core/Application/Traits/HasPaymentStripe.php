<?php

namespace App\Core\Application\Traits;

use App\Core\Application\Factories\Payments\Stripe\StripePaymentMethodDetailsFactory;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Utils\Helpers\Money;
use Stripe\Charge;

trait HasPaymentStripe
{

    private PaymentRepInterface $repo;
    public function setRepository(PaymentRepInterface $repo): void
    {
        $this->repo = $repo;
    }
    public function updatePaymentWithStripeData(Payment $payment,Charge $charge, ?PaymentMethod $savedPaymentMethod): Payment
    {
        $expected = Money::from($payment->amount);
        $currentReceived = Money::from($payment->amount_received ?? '0.00');
        $transactionReceived = Money::from(
            $charge->amount_captured ?? 0
        )->divide('100');
        $totalReceived = $currentReceived->add($transactionReceived);
        $internalStatus = $this->verifyStatus($charge, $totalReceived, $expected);
        $paymentMethodDetails = StripePaymentMethodDetailsFactory::fromStripe($charge->payment_method_details);
        $fields=[
            'payment_intent_id' => $charge->payment_intent,
            'payment_method_id' => $savedPaymentMethod?->id,
            'stripe_payment_method_id' => $charge?->payment_method,
            'amount_received' => $totalReceived->finalize(),
            'status' => $internalStatus,
            'payment_method_details'=>$paymentMethodDetails,
            'url' => $charge?->receipt_url ?? $payment->url,
        ];
        $fields = array_filter($fields, fn($value) => !is_null($value));
        return $this->repo->update($payment->id, $fields);
    }

    public function verifyStatus(Charge $charge, Money $received, Money $expected): PaymentStatus
    {
        if ($charge->status !== 'succeeded') {
            return PaymentStatus::PAID;
        }

        if ($received->isLessThan($expected)) {
            return PaymentStatus::UNDERPAID;
        }

        if ($received->isGreaterThan($expected)) {
            return PaymentStatus::OVERPAID;
        }

        return PaymentStatus::SUCCEEDED;

    }
}
