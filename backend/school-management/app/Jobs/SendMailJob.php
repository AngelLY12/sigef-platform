<?php

namespace App\Jobs;

use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 5;
    public $backoff = [10, 30, 60];

    protected Mailable $mailable;
    protected string $recipientEmail;
    protected ?string $jobType = null;
    protected ?int $paymentEventId = null;


    /**
     * Create a new job instance.
     */
    public function __construct(Mailable $mailable, string $recipientEmail,  ?string $jobType = null, ?int $paymentEventId = null)
    {
        $this->mailable = $mailable;
        $this->recipientEmail = $recipientEmail;
        $this->jobType = $jobType;
        $this->paymentEventId = $paymentEventId;
    }
    public function retryUntil()
    {
        return now()->addHours(2);
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentEventRepInterface $paymentEventRep, PaymentEventQueryRepInterface $paymentEventQueryRep): void
    {
        if ($this->paymentEventId) {
            $this->updateEmailStatus($paymentEventRep, $paymentEventQueryRep,'sending', null);
        }
        try {
            Mail::to($this->recipientEmail)->send($this->mailable);
            if ($this->paymentEventId) {
                $this->updateEmailStatus($paymentEventRep, $paymentEventQueryRep,'delivered', null);
            }
            $logContext = [
                'email' => $this->recipientEmail,
                'job_type' => $this->jobType,
                'mailable' => get_class($this->mailable)
            ];

            Log::info("Correo enviado exitosamente", $logContext);
        } catch (\Throwable $e) {
            if ($this->paymentEventId) {
                $this->updateEmailStatus($paymentEventRep, $paymentEventQueryRep,'failed', $e->getMessage());
            }
            $this->handleError($e);
        }
    }

    private function updateEmailStatus(
        PaymentEventRepInterface $repo,
        PaymentEventQueryRepInterface $queryRep,
        string $status,
        ?string $error = null
    ): void {
        $event = $queryRep->findById($this->paymentEventId);

        if (!$event) {
            Log::warning("PaymentEvent no encontrado para email", [
                'event_id' => $this->paymentEventId,
                'email' => $this->recipientEmail
            ]);
            return;
        }

        $updateData = [
            'metadata' => array_merge($event->metadata ?? [], [
                'email_status' => $status,
                'last_updated_at' => now()->toISOString(),
            ])
        ];

        switch ($status) {
            case 'sending':
                $updateData['metadata']['attempt_count'] =
                    ($event->metadata['attempt_count'] ?? 0) + 1;
                $updateData['metadata']['last_attempt_at'] = now()->toISOString();
                break;

            case 'delivered':
                $updateData['processed'] = true;
                $updateData['processed_at'] = now();
                $updateData['metadata']['delivered_at'] = now()->toISOString();
                $updateData['metadata']['recipient_email'] = $this->recipientEmail;
                break;

            case 'failed':
                $updateData['error_message'] = $error;
                $updateData['metadata']['last_error'] = $error;
                $updateData['metadata']['failed_at'] = now()->toISOString();
                break;
        }

        $repo->update($this->paymentEventId, $updateData);
    }

    private function handleError(\Throwable $e): void
    {
        $message = $e->getMessage();
        $logContext = [
            'email' => $this->recipientEmail,
            'job_type' => $this->jobType,
            'mailable' => get_class($this->mailable),
            'error' => $message,
            'attempt' => $this->attempts()
        ];

        if ($this->isRateLimitError($message)) {
            Log::warning("Rate limit detectado", $logContext);

            $delay = min(300, pow(2, $this->attempts()) * 10); // 10, 40, 90, 160, 300 segundos
            $this->release($delay);
            return;
        }

        Log::error("Error al enviar correo", $logContext);

        if ($this->isTransientError($message)) {
            $this->release(60);
            return;
        }

        if ($this->isPermanentError($message)) {
            Log::error("Error permanente, no se reintentará", $logContext);
            return;
        }

        throw $e;
    }

    private function isRateLimitError(string $message): bool
    {
        return str_contains($message, '429') ||
            str_contains($message, 'Too Many Requests') ||
            str_contains($message, 'rate limit');
    }

    private function isTransientError(string $message): bool
    {
        return str_contains($message, 'Connection') ||
            str_contains($message, 'timeout') ||
            str_contains($message, 'temporarily') ||
            str_contains($message, 'retry') ||
            str_contains($message, '550') ||
            str_contains($message, '552');
    }

    private function isPermanentError(string $message): bool
    {
        return str_contains($message, 'Invalid address') ||
            str_contains($message, 'Mailbox not found') ||
            str_contains($message, 'User unknown') ||
            str_contains($message, '550') ||
            str_contains($message, '554');
    }

    public static function forUser(Mailable $mailable, string $recipientEmail, ?string $jobType = null, ?int $paymentEventId=null): PendingDispatch
    {
         return self::dispatch($mailable, $recipientEmail, $jobType, $paymentEventId);
    }

    public static function fromBulkRetry(Mailable $mailable, string $recipientEmail): PendingDispatch
    {
        return self::dispatch($mailable, $recipientEmail, 'bulk_retry');

    }
}
