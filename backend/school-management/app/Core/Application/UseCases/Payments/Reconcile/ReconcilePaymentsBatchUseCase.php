<?php

namespace App\Core\Application\UseCases\Payments\Reconcile;

use App\Core\Application\DTO\Response\General\ReconciliationResult;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Events\PaymentReconciledBatchEvent;
use App\Jobs\ClearCacheForUsersJob;
use App\Jobs\ClearStaffCacheJob;
use App\Jobs\SendBulkMailJob;
use App\Mail\PaymentValidatedMail;

class ReconcilePaymentsBatchUseCase extends BaseReconciliationUseCase
{

    private const BATCH_SIZE = 100;
    private const STRIPE_CHUNK_SIZE = 20;


    public function execute(): ReconciliationResult
    {
        $result = new ReconciliationResult();

        try {
            $paymentIds = $this->paymentEventRep->getPaymentsNeedingReconciliation();
            if (empty($paymentIds)) {
                logger()->info('Batch: No hay pagos que necesiten reconciliación');
                return $result;
            }

            $payments = $this->filterReconcilablePayments($paymentIds);

            if (empty($payments)) {
                logger()->info('Batch: No hay pagos reconciliables después del filtro');
                return $result;
            }

            $this->processInBatches($payments, $result);

            event(new PaymentReconciledBatchEvent(
                result: [
                    'processed' => $result->processed,
                    'updated' => $result->updated,
                    'failed' => $result->failed,
                    'completed_at' => now()->toISOString()
                ],
                outcome: 'batch_completed',
                eventType: PaymentEventType::RECONCILIATION_BATCH_COMPLETED->value
            ));

            logger()->info('Batch reconciliation completed', (array) $result);

        }catch (\Throwable $e){
            event(new PaymentReconciledBatchEvent(
                result: [
                    'processed' => $result->processed,
                    'updated' => $result->updated,
                    'failed' => $result->failed,
                    'failed_at' => now()->toISOString()
                ],
                outcome: 'batch_failed',
                eventType: PaymentEventType::RECONCILIATION_BATCH_FAILED->value,
                error: $e->getMessage()
            ));

            logger()->error('Batch reconciliation failed: ' . $e->getMessage());
            throw $e;


        }

        return $result;
    }

    private function filterReconcilablePayments(array $paymentIds): array
    {
        $payments = [];

        foreach (array_chunk($paymentIds, self::BATCH_SIZE) as $idChunk) {
            $chunkPayments = $this->paymentQueryRep->findByIds($idChunk);

            foreach ($chunkPayments as $payment) {
                if ($this->shouldReconcileInBatch($payment)) {
                    $payments[] = $payment;
                }
            }
        }

        return $payments;
    }

    private function shouldReconcileInBatch($payment): bool
    {
        if (!in_array($payment->status, PaymentStatus::reconcilableStatuses())) {
            return false;
        }

        if (!$payment->payment_intent_id) {
            return false;
        }

        if (in_array($payment->status->value, PaymentStatus::terminalStatuses())) {
            return false;
        }

        return true;
    }

    private function processInBatches(array $payments, ReconciliationResult $result): void
    {
        $batches = $this->groupPaymentsByIntent($payments);

        foreach ($batches as $batch) {
            $this->processSingleBatch($batch, $result);

            usleep(50000);
        }
    }

    private function groupPaymentsByIntent(array $payments): array
    {
        $batches = [];
        $currentBatch = [];

        foreach ($payments as $payment) {
            $currentBatch[] = $payment;

            if (count($currentBatch) >= self::STRIPE_CHUNK_SIZE) {
                $batches[] = $currentBatch;
                $currentBatch = [];
            }
        }

        if (!empty($currentBatch)) {
            $batches[] = $currentBatch;
        }

        return $batches;
    }

    private function processSingleBatch(array $payments, ReconciliationResult $result): void
    {
        $paymentIntentIds = array_map(fn($p) => $p->payment_intent_id, $payments);
        $stripeData = $this->stripe->getIntentsAndChargesBatch($paymentIntentIds);
        $affectedUsers = [];
        $paymentsByUserId = [];

        foreach ($payments as $payment) {
            $result->processed++;

            try {
                if (!isset($stripeData[$payment->payment_intent_id])) {
                    $result->failed++;
                    continue;
                }

                [$pi, $charge] = $stripeData[$payment->payment_intent_id];

                $updatedPayment = $this->processReconciliation($payment, $pi, $charge);

                if ($updatedPayment) {
                    $result->updated++;
                    $paymentsByUserId[$updatedPayment->user_id][] = $updatedPayment;
                    $affectedUsers[] = $updatedPayment->user_id;
                }

            } catch (\Throwable $e) {
                $result->failed++;
                logger()->warning("Batch error for payment {$payment->id}: " . $e->getMessage());
            }
        }
        if($result->updated>0)
        {
            $this->clearCaches($affectedUsers);
            $this->notifyUsersBatch($paymentsByUserId);
        }
    }

    private function notifyUsersBatch(array $paymentsByUserId): void
    {
        if (empty($paymentsByUserId)) {
            return;
        }

        $userIds = array_keys($paymentsByUserId);
        $users = $this->userRepo->findByIds($userIds);

        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user->id] = $user;
        }
        $mailables = [];
        $recipientEmails = [];

        foreach ($paymentsByUserId as $userId => $userPayments) {
            $user = $userMap[$userId] ?? null;
            if (!$user) {
                continue;
            }

            foreach ($userPayments as $payment) {
                $data = [
                    'recipientName' => $user->fullName(),
                    'recipientEmail' => $user->email,
                    'concept_name' => $payment->concept_name,
                    'amount' => $payment->amount,
                    'amount_received' => $payment->amount_received,
                    'payment_method_detail' => $payment->payment_method_details ?? [],
                    'status' => $payment->status->value,
                    'url' => $payment->url ?? null,
                    'payment_intent_id' => $payment->payment_intent_id,
                ];

                $mailables[] = new PaymentValidatedMail(
                    MailMapper::toPaymentValidatedEmailDTO($data)
                );
                $recipientEmails[] = $user->email;
            }
        }

        if (!empty($mailables)) {
            $this->sendBulkEmails($mailables, $recipientEmails);
        }
    }

    private function clearCaches(array $affectedUsers): void
    {
        $uniqueUserIds = array_unique($affectedUsers);

        foreach (array_chunk($uniqueUserIds, 100) as $chunk) {
            ClearCacheForUsersJob::forStudents($chunk)
                ->onQueue('cache')
                ->delay(now()->addSeconds(5));
        }

        ClearStaffCacheJob::dispatch()
            ->onQueue('cache')
            ->delay(now()->addSeconds(5));
    }

    private function sendBulkEmails(array $mailables, array $recipientEmails): void
    {
        $chunkSize = 100;
        $total = count($mailables);

        for ($i = 0; $i < $total; $i += $chunkSize) {
            $mailablesChunk = array_slice($mailables, $i, $chunkSize);
            $emailsChunk = array_slice($recipientEmails, $i, $chunkSize);

            SendBulkMailJob::forRecipients(
                $mailablesChunk,
                $emailsChunk,
                'bulk_reconcile_payment'
            )
                ->onQueue('emails')
                ->delay(now()->addSeconds(5));
        }
    }

}
