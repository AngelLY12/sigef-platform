<?php

namespace App\Core\Domain\Entities;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use DateTime;

/**
 * @OA\Schema(
 *     schema="PaymentEvent",
 *     type="object",
 *     required={"eventType","amountReceived","processed","retryCount"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="paymentId", type="integer", nullable=true),
 *     @OA\Property(property="stripeEventId", type="string", nullable=true),
 *     @OA\Property(property="stripePaymentIntentId", type="string", nullable=true),
 *     @OA\Property(property="stripeSessionId", type="string", nullable=true),
 *     @OA\Property(property="eventType", ref="#/components/schemas/PaymentEventType"),
 *     @OA\Property(property="metadata", type="object", nullable=true),
 *     @OA\Property(property="amountReceived", type="string", nullable=true),
 *     @OA\Property(property="status", ref="#/components/schemas/PaymentStatus", nullable=true),
 *     @OA\Property(property="processed", type="boolean"),
 *     @OA\Property(property="errorMessage", type="string", nullable=true),
 *     @OA\Property(property="retryCount", type="integer"),
 *     @OA\Property(property="processedAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="createdAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", nullable=true),
 * )
 */
class PaymentEvent
{

    public function __construct(
        public ?int $id = null,
        public ?int $paymentId = null,
        public ?string $stripeEventId = null,
        public ?string $stripePaymentIntentId = null,
        public ?string $stripeSessionId = null,
        public PaymentEventType $eventType,
        public ?PaymentEventMetadata $metadata = null,
        public ?string $amountReceived,
        public ?PaymentStatus $status = null,
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
        PaymentEventMetadata $metadata,
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

    public function registerRetry(): void
    {
        $this->retryCount++;
    }

    public function markAsProcessed(): void
    {
        $this->processed = true;
        $this->processedAt = new DateTime();
        $this->errorMessage = null;
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->processed = false;
        $this->errorMessage = $errorMessage;
        $this->registerRetry();
    }

    public function clearError(): void
    {
        $this->errorMessage = null;
    }

    public function setStatus(PaymentStatus $status): void
    {
        $this->status = $status;
    }

    public function setAmountReceived(string $amountReceived): void
    {
        $this->amountReceived = $amountReceived;
    }

}
