<?php

namespace App\Jobs;

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
    protected ?string $jobType = null;
    public function __construct(array $mailables, array $recipientEmails, ?string $jobType = null)
    {
        if (count($mailables) !== count($recipientEmails)) {
            throw new \InvalidArgumentException('Mailables y emails deben tener misma cantidad');
        }
        $this->mailables = $mailables;
        $this->recipientEmails = $recipientEmails;
        $this->jobType = $jobType;
    }
    public function retryUntil()
    {
        return now()->addMinutes(10);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $emailCount = 0;
        $successCount = 0;
        $failCount = 0;

        foreach ($this->mailables as $index => $mailable) {
            $recipientEmail = $this->recipientEmails[$index];

            try {
                if ($this->shouldThrottle($startTime, $emailCount)) {
                    $this->throttle($startTime);
                    $startTime = microtime(true);
                    $emailCount = 0;
                }

                Mail::to($recipientEmail)->send($mailable);
                $successCount++;
                $emailCount++;


                usleep(self::DELAY_BETWEEN_EMAILS);

            } catch (\Throwable $e) {
                $failCount++;
                $this->handleEmailError($e, $recipientEmail, $mailable);
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

    private function handleEmailError(\Throwable $e, string $recipientEmail, Mailable $mailable): void
    {
        $message = $e->getMessage();

        if (str_contains($message, '429') || str_contains($message, 'Too Many Requests')) {
            Log::warning("SendBulkMailJob: Rate limit detectado", [
                'email' => $recipientEmail,
                'job_type' => $this->jobType
            ]);
            return;
        }

        Log::error("SendBulkMailJob: Error al enviar email", [
            'email' => $recipientEmail,
            'error' => $message,
            'job_type' => $this->jobType
        ]);

        SendMailJob::fromBulkRetry(clone $mailable, $recipientEmail)
            ->onQueue('emails')
            ->delay(now()->addMinutes(1));
    }

    public static function forRecipients(
        array $mailables,
        array $recipientEmails,
        ?string $jobType = null
    ): PendingDispatch {
        return self::dispatch($mailables, $recipientEmails, $jobType);
    }
}
