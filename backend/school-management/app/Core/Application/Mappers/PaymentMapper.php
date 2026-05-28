<?php

namespace App\Core\Application\Mappers;


use App\Core\Application\DTO\Response\Payment\FinancialSummaryResponse;
use App\Core\Application\DTO\Response\Payment\PaymentDataResponse;
use App\Core\Application\DTO\Response\Payment\PaymentHistoryResponse;
use App\Core\Application\DTO\Response\Payment\PaymentListItemResponse;
use App\Core\Application\DTO\Response\Payment\PaymentsMadeByConceptName;
use App\Core\Application\DTO\Response\Payment\PaymentsSummaryResponse;
use App\Core\Application\DTO\Response\Payment\PaymentToDisplay;
use App\Core\Application\DTO\Response\Payment\PaymentValidateResponse;
use App\Core\Application\DTO\Response\User\UserDataResponse;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Utils\Helpers\Money;
use App\Models\Payment;
use App\Core\Domain\Entities\Payment as DomainPayment;
use Stripe\Checkout\Session;

class PaymentMapper{

    public static function toDomain(PaymentConcept $concept, int $userId, Session $session): DomainPayment
    {
        return new DomainPayment(
            concept_name: $concept->concept_name,
            amount: $concept->amount,
            status: EnumMapper::fromStripe($session->payment_status),
            payment_method_details: [],
            id: null,
            user_id: $userId,
            payment_concept_id: $concept->id,
            payment_method_id: null,
            stripe_payment_method_id: null,
            amount_received: null,
            payment_intent_id: null,
            url: $session->url ?? null,
            stripe_session_id: $session->id ?? null,
            created_at: null,
            updated_at: null
        );
    }

    public static function toHistoryResponse(Payment $payment): PaymentHistoryResponse
    {
        $status = config("payments_ui.statuses.{$payment->status->value}", $payment->status->value);
        return new PaymentHistoryResponse(
            id: $payment->id ?? null,
            concept: $payment->concept_name ?? null,
            amount: $payment->amount ?? null,
            amount_received: $payment->amount_received ?? null,
            status: $status ?? null,
            date: $payment->created_at->diffForHumans(),
            date_iso: $payment->created_at->format('Y-m-d H:i:s')
        );
    }
    public static function toPaymentToDisplay(Payment $payment): PaymentToDisplay
    {
        $domainPayment= $payment->toDomain();
        $balance = null;
        if ($domainPayment->isOverPaid()) {
            $balance = $domainPayment->getOverPaidAmount();
        } elseif ($domainPayment->isUnderPaid()) {
            $balance = '-' . $domainPayment->getPendingAmount();
        }
        $status = config("payments_ui.statuses.{$payment->status->value}", $payment->status->value);
        return new PaymentToDisplay(
            id: $payment->id,
            concept_name: $payment->concept_name,
            amount: $payment->amount,
            status: $status,
            created_at_human: $payment->created_at->diffForHumans(),
            has_pending_amount: $payment->amount_received < $payment->amount,
            balance: $balance ?? 'N/A',
            payment_method_details: $payment->payment_method_details ? : null,
            amount_received: $payment->amount_received ?? null,
            reference: $payment->payment_intent_id ?? null,
            url: $payment->url ?? null,
        );
    }

    public static function toPaymentDataResponse(DomainPayment $payment): PaymentDataResponse{
        return new PaymentDataResponse(
            id:$payment->id ?? null,
            amount:$payment->amount ?? null,
            amount_received: $payment->amount_received ?? null,
            status:$payment->status->value ?? null,
            payment_intent_id:$payment->payment_intent_id ?? null
        );

    }

     public static function toPaymentValidateResponse(UserDataResponse $student, PaymentDataResponse $payment, array $metadata): PaymentValidateResponse
    {
        return new PaymentValidateResponse(
            student: new UserDataResponse(
                id: $student->id ?? null,
                fullName: $student->fullName ?? null,
                email: $student->email ?? null,
                curp: $student->curp ?? null,
                n_control: $student->n_control ?? null
            ),
            payment: new PaymentDataResponse(
                id: $payment->id ?? null,
                amount: $payment->amount ?? null,
                amount_received: $payment->amount_received ?? null,
                status: $payment->status ?? null,
                payment_intent_id: $payment->payment_intent_id ?? null,
            ),
            updatedAt: now()->format('Y-m-d H:i:s'),
            metadata: $metadata
        );
    }

    public static function toListItemResponse(Payment $payment): PaymentListItemResponse
    {
        $type = $payment->payment_method_details['type'] ?? 'desconocido';
        return new PaymentListItemResponse(
            id: $payment->id,
            date:$payment->created_at ? $payment->created_at->format('Y-m-d H:i:s'): null,
            concept: $payment->concept_name ?? null,
            amount: $payment->amount ?? null,
            amount_received: $payment->amount_received ?? null,
            method: $type ?? null,
            userId: $payment->user->id ?? null,
            fullName: $payment->user ? $payment->user->name . ' ' . $payment->user->last_name : null,
        );
    }

    public static function toFinancialSummaryResponse(
        $totalPayments, $paymentsBySemester, $totalPayouts,
        $totalFees, $payoutsBySemester, $totalAvailable, $totalPending,
        $availableBySource, $pendingBySource, $availablePercentage,
        $pendingPercentage, $netReceivedPercentage, $feePercentage, $netAfterFeesPercentage,$totalNetReceived, $totalNetAfterFees,
        $feesBySemester): FinancialSummaryResponse
    {
        return new FinancialSummaryResponse(
            totalPayments: $totalPayments,
            totalPayouts: $totalPayouts,
            totalFees: $totalFees,
            totalNetReceived: $totalNetReceived,
            totalNetAfterFees: $totalNetAfterFees,
            paymentsBySemester: $paymentsBySemester,
            payoutsBySemester: $payoutsBySemester,
            feesBySemester: $feesBySemester,
            totalBalanceAvailable: $totalAvailable,
            totalBalancePending: $totalPending,
            availablePercentage: $availablePercentage,
            pendingPercentage: $pendingPercentage,
            netReceivedPercentage: $netReceivedPercentage,
            feePercentage: $feePercentage,
            netAfterFeesPercentage: $netAfterFeesPercentage,
            totalBalanceAvailableBySource: $availableBySource,
            totalBalancePendingBySource: $pendingBySource,
        );

    }

    public static function toPaymentsSummaryResponse(array $data): PaymentsSummaryResponse
    {
        return new PaymentsSummaryResponse(
            totalPayments: $data['total'],
            paymentsByMonth: $data['by_month'],
        );
    }

    public static function toPaymentsMadeByConceptName(Payment $data): PaymentsMadeByConceptName
    {
        $amountTotal = Money::from($data->amount_total ?? '0.00');
        $amountReceived = Money::from($data->amount_received_total ?? '0.00');

        $collectionRate = '0.00';
        if ($amountReceived->isPositive() && !$amountTotal->isZero()) {
            $collectionRate= $amountReceived
                ->divide($amountTotal)
                ->multiply('100')
                ->finalize();
        }
        return new PaymentsMadeByConceptName(
            concept_name: $data->concept_name ?? 'Unknown',
            amount_total: $amountTotal->finalize(),
            amount_received_total: $amountReceived->finalize(),
            first_payment_date: $data->first_payment_date ?? 's/f',
            last_payment_date: $data->last_payment_date ?? 's/f',
            collection_rate: $collectionRate,
        );

    }


}
