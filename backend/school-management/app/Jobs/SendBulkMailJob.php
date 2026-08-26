<?php

namespace App\Jobs;

use App\Core\Domain\Repositories\Command\Events\EmailEventRepInterface;
use App\Core\Domain\Repositories\Query\Events\EmailEventQueryRepInterface;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBulkMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public int $tries = 5;
    public $backoff = [10, 30, 60];
    private const EMAILS_PER_MINUTE = 1100;
    private const DELAY_BETWEEN_EMAILS = 50000;

    protected array $mailables;
    protected array $recipientEmails;
    protected array $emailEventIds;
    protected ?string $jobType = null;
    public function __construct(
        array $mailables,
        array $recipientEmails,
        array $emailEventIds,
        ?string $jobType = null)
    {
        if (
            count($mailables) !== count($recipientEmails) ||
            count($mailables) !== count($emailEventIds)
        ) {
            throw new \InvalidArgumentException(
                'Mailables, emails y EmailEvent IDs deben tener la misma cantidad'
            );
        }
        $this->mailables = $mailables;
        $this->recipientEmails = $recipientEmails;
        $this->emailEventIds = $emailEventIds;
        $this->jobType = $jobType;
    }
    public function retryUntil()
    {
        return now()->addMinutes(10);
    }

    /**
     * Execute the job.
     */
    public function handle(
        EmailEventRepInterface $emailEventRep,
        EmailEventQueryRepInterface $emailEventQueryRep
    ): void
    {
        $startTime = microtime(true);
        $emailCount = 0;
        $successCount = 0;
        $failCount = 0;

        foreach ($this->mailables as $index => $mailable) {
            $recipientEmail = $this->recipientEmails[$index];
            $emailEventId = $this->emailEventIds[$index];
            $emailEvent = $emailEventQueryRep->findById($emailEventId);

            if (!$emailEvent) {
                Log::warning(
                    'EmailEvent no encontrado para email bulk',
                    [
                        'event_id' => $emailEventId,
                        'email' => $recipientEmail,
                    ]
                );

                $failCount++;
                continue;
            }

            if (
                $emailEvent->alreadySent() ||
                $emailEvent->alreadyDelivered()
            ) {
                continue;
            }

            $emailEvent->registerAttempt();
            $emailEventRep->save($emailEvent);
            try {
                if ($this->shouldThrottle($startTime, $emailCount)) {
                    $this->throttle($startTime);
                    $startTime = microtime(true);
                    $emailCount = 0;
                }

                Mail::to($recipientEmail)->send($mailable);
                $emailEvent->markAsSent();
                $emailEventRep->save($emailEvent);
                $successCount++;
                $emailCount++;

                Log::info('Correo bulk enviado exitosamente', [
                    'event_id' => $emailEventId,
                    'email' => $recipientEmail,
                    'job_type' => $this->jobType,
                    'mailable' => get_class($mailable),
                ]);

                usleep(self::DELAY_BETWEEN_EMAILS);

            } catch (\Throwable $e) {
                $failCount++;
                $emailEvent->markAsFailed($e->getMessage());
                $emailEventRep->save($emailEvent);
                $this->handleEmailError($e, $recipientEmail, $mailable, $emailEventId);
                continue;
            }
        }
        Log::info("SendBulkMailJob completado", [
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'total_emails' => count($this->mailables),
            'job_type' => $this->jobType,
            'duration_seconds' => round(microtime(true) - $startTime, 2)
        ]);


        if ($successCount === 0 && $failCount > 0) {
            throw new \RuntimeException("Todos los emails fallaron");
        }
    }

    private function shouldThrottle(float $startTime, int $emailCount): bool
    {
        return $emailCount >= self::EMAILS_PER_MINUTE;
    }

    private function throttle(float $startTime): void
    {
        $elapsed = microtime(true) - $startTime;
        $waitTime = max(0, 60 - $elapsed);

        if ($waitTime > 0) {
            Log::info("SendBulkMailJob: Rate limiting, esperando {$waitTime} segundos", [
                'job_type' => $this->jobType
            ]);
            sleep($waitTime);
        }
    }

    private function handleEmailError(
        \Throwable $e,
        string $recipientEmail,
        Mailable $mailable,
        int $emailEventId
    ): void
    {
        $message = $e->getMessage();

        if (str_contains($message, '429') || str_contains($message, 'Too Many Requests')) {
            Log::warning("SendBulkMailJob: Rate limit detectado", [
                'email' => $recipientEmail,
                'job_type' => $this->jobType
            ]);
            SendMailJob::fromBulkRetry(
                mailable: clone $mailable,
                recipientEmail: $recipientEmail,
                emailEventId: $emailEventId,
            )
                ->onQueue('emails')
                ->delay(now()->addMinutes(1));
            return;
        }

        Log::error("SendBulkMailJob: Error al enviar email", [
            'email' => $recipientEmail,
            'error' => $message,
            'job_type' => $this->jobType
        ]);

        SendMailJob::fromBulkRetry(
            mailable: clone $mailable,
            recipientEmail: $recipientEmail,
            emailEventId: $emailEventId,
        )
            ->onQueue('emails')
            ->delay(now()->addMinutes(1));
    }

    public static function forRecipients(
        array $mailables,
        array $recipientEmails,
        array $emailEventIds,
        ?string $jobType = null
    ): PendingDispatch {
        return self::dispatch($mailables, $recipientEmails, $emailEventIds, $jobType);
    }
}
