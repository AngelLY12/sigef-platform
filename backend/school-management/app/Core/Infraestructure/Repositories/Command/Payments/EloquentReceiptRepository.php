<?php

namespace App\Core\Infraestructure\Repositories\Command\Payments;

use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\ReceiptRepInterface;
use App\Core\Domain\Utils\Helpers\Folio;
use App\Exceptions\NotAllowed\NotAllowedException;
use App\Exceptions\Validation\PaymentIsNotPaidException;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentReceiptRepository implements ReceiptRepInterface
{
    public function getOrCreateReceipt(int $paymentId): ?Receipt
    {

        return DB::transaction(function () use ($paymentId) {
            $userId = Auth::id();
            $payment = Payment::with(['user:id,name,last_name,email'])
                ->where('id', $paymentId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();
            if (!$payment) {
                return null;
            }

            if(!in_array($payment->status->value, PaymentStatus::receivedStatuses()))
            {
                throw new PaymentIsNotPaidException();
            }

            $receipt = Receipt::where('payment_id', $paymentId)->first();
            if($receipt)
            {
                return $receipt;
            }

            $receipt = Receipt::create([
               'payment_id' => $payment->id,
               'folio' => 'PENDING_' . uniqid(),
               'payer_name' => "{$payment->user->name} {$payment->user->last_name}",
               'payer_email' => $payment->user->email,
               'concept_name' => $payment->concept_name,
               'amount' => $payment->amount,
               'amount_received' => $payment->amount_received,
                'transaction_reference' => $payment->payment_intent_id,
                'metadata' => [
                    'payment_date' => $payment->created_at,
                    'payment_method_details' => $payment->payment_method_details,
                    'stripe_receipt' => $payment->url ?? null
                ],
                'issued_at' => now()
            ]);
            $receipt->folio = Folio::generateReceiptFolio($payment->concept_name, $receipt->id);
            $receipt->save();
            return $receipt;
        });
    }

    public function findByFolio(string $folio): Receipt
    {
        return Receipt::where('folio', $folio)->firstOrFail();
    }

}
