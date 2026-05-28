<?php

namespace App\Core\Domain\Entities;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use DateTime;
class PaymentEvent
{

    public function __construct(
        public ?int $id,
        public ?int $paymentId,
        public ?string $stripeEventId,
        public ?string $stripePaymentIntentId,
        public ?string $stripeSessionId,
        public PaymentEventType $eventType,
        public ?array $metadata,
        public ?string $amountReceived,
        public ?PaymentStatus $status,
        public bool $processed = false,
        public ?string $errorMessage = null,
        public int $retryCount = 0,
        public ?DateTime $processedAt = null,
        public ?DateTime $createdAt = null,
        public ?DateTime $updatedAt = null,
    ){}

    public static function createWebhookEvent(
        ?int $paymentId,
        string $stripeEventId,
        ?string $paymentIntentId,
        ?string $sessionId,
        ?string $amount,
        PaymentEventType $eventType,
        array $metadata,
    ): self {
        return new self(
            id: null,
            paymentId: $paymentId,
            stripeEventId: $stripeEventId,
            stripePaymentIntentId: $paymentIntentId,
            stripeSessionId: $sessionId,
            eventType: $eventType,
            metadata: $metadata,
            amountReceived: $amount,
            status: null,
            processed: false
        );
    }

    public static function createReconciliationEvent(
        ?int $paymentId,
        string $outcome,
        ?string $stripeEventId,
        ?string $stripeSessionId,
        PaymentEventType $eventType,
        ?string $paymentIntentId,
        array $metadata = [],
        ?string $amount = null,
        ?string $error = null,
        ?PaymentStatus $status = null,
        bool $processed = true
    ): self {

        return new self(
            id: null,
            paymentId: $paymentId,
            stripeEventId: $stripeEventId,
            stripePaymentIntentId: $paymentIntentId,
            stripeSessionId: $stripeSessionId,
            eventType: $eventType,
            metadata: array_merge($metadata, ['outcome' => $outcome]),
            amountReceived: $amount,
            status: $status,
            processed: $processed,
            errorMessage: $error,
            processedAt: $processed ? new \DateTime() : null
        );
    }

    public static function createEmailEvent(
        ?int $paymentId,
        string $eventId,
        ?string $paymentIntentId,
        ?string $sessionId,
        PaymentEventType $eventType,
        string $recipientEmail,
        array $emailData = [],
        string $initialStatus = 'pending'
    ): self {
        if (!$eventType->isEmail()) {
            throw new \InvalidArgumentException("El tipo de evento debe ser un email");
        }

        return new self(
            id: null,
            paymentId: $paymentId,
            stripeEventId: $eventId,
            stripePaymentIntentId: $paymentIntentId,
            stripeSessionId: $sessionId,
            eventType: $eventType,
            metadata: array_merge($emailData, [
                'email_status' => $initialStatus,
                'recipient_email' => $recipientEmail,
                'initial_status' => $initialStatus,
                'created_at' => (new DateTime())->format('c'),
                'attempt_count' => 0,
                'last_attempt_at' => null,
                'delivered_at' => null,
                'failed_at' => null,
            ]),
            amountReceived: null,
            status: null,
            processed: $initialStatus === 'delivered',
            processedAt: $initialStatus === 'delivered' ? new DateTime() : null,
        );
    }

    public function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }
    public function setProcessedAt(DateTime $processedAt): void
    {
        $this->processedAt = $processedAt;
    }

    public function setProccessed(bool $processed): void
    {
        $this->processed = $processed;
    }

    public function setStatus(PaymentStatus $status): void
    {
        $this->status = $status;
    }

}
