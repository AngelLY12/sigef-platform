<?php

namespace App\Core\Application\DTO\Request\Events\PaymentEvent;

use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use Carbon\Carbon;

class PaymentEventFilters
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?int $paymentId = null,
        public ?PaymentEventType $eventType = null,
        public ?bool $processed = null,
        public ?string $stripePaymentIntentId = null,
        public ?string $stripeSessionId = null,
        public ?Carbon $from = null,
        public ?Carbon $to = null,
    ){}

    public static function create(
        int $page = 1,
        int $perPage = 20,
        ?int $paymentId = null,
        ?PaymentEventType $eventType = null,
        ?bool $processed = null,
        ?string $stripePaymentIntentId = null,
        ?string $stripeSessionId = null,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): self {
        return new self(
            page: $page,
            perPage: $perPage,
            paymentId: $paymentId,
            eventType: $eventType,
            processed: $processed,
            stripePaymentIntentId: $stripePaymentIntentId,
            stripeSessionId: $stripeSessionId,
            from: $from,
            to: $to,
        );
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'perPage' => $this->perPage,
            'paymentId' => $this->paymentId,
            'eventType' => $this->eventType?->value,
            'processed' => $this->processed,
            'stripePaymentIntentId' => $this->stripePaymentIntentId,
            'stripeSessionId' => $this->stripeSessionId,
            'from' => $this->from?->format('Y-m-d H:i:s'),
            'to' => $this->to?->format('Y-m-d H:i:s'),
        ];
    }

}
