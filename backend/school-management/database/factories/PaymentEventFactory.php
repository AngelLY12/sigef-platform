<?php

namespace Database\Factories;

use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentEvent>
 */
class PaymentEventFactory extends Factory
{
    protected $model = PaymentEvent::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        $eventTypes = PaymentEventType::cases();
        $statuses = PaymentStatus::cases();

        return [
            'payment_id' => Payment::factory(),
            'stripe_event_id' => 'evt_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'stripe_payment_intent_id' => 'pi_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'stripe_session_id' => 'cs_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'event_type' => $this->faker->randomElement($eventTypes),
            'metadata' => [
                'billing_details' => [
                    'email' => $this->faker->email,
                    'name' => $this->faker->name,
                ],
                'payment_method' => $this->faker->creditCardType,
                'amount_captured' => $this->faker->numberBetween(1000, 10000),
            ],
            'amount_received' => $this->faker->randomFloat(2, 10, 1000),
            'processed' => false,
            'error_message' => null,
            'retry_count' => 0,
            'processed_at' => null,
            'status' => $this->faker->randomElement($statuses),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the event is processed.
     */
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed' => true,
            'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the event has failed.
     */
    public function failed(string $error = null): static
    {
        return $this->state(fn (array $attributes) => [
            'processed' => false,
            'error_message' => $error ?? 'Payment processing failed',
            'retry_count' => $this->faker->numberBetween(1, 2),
        ]);
    }

    /**
     * Indicate that the event has exceeded max retries.
     */
    public function maxRetriesExceeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed' => true,
            'error_message' => 'Max retries exceeded',
            'retry_count' => 3,
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate the event type.
     */
    public function eventType(PaymentEventType $eventType): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => $eventType,
        ]);
    }

    /**
     * Indicate the payment status.
     */
    public function status(PaymentStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate the payment ID.
     */
    public function forPayment(int $paymentId): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_id' => $paymentId,
        ]);
    }

    /**
     * Indicate a specific retry count.
     */
    public function retryCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'retry_count' => max(0, $count),
        ]);
    }

    /**
     * Indicate the amount received.
     */
    public function amountReceived(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_received' => $amount,
        ]);
    }

    /**
     * Indicate a payment_intent.created event.
     */
    public function sessoionAsyncComplete(): static
    {
        return $this->eventType(PaymentEventType::WEBHOOK_SESSION_ASYNC_COMPLETED);
    }

    /**
     * Indicate a payment_intent.succeeded event.
     */
    public function sessionComplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => PaymentEventType::WEBHOOK_SESSION_COMPLETED,
            'status' => PaymentStatus::PAID,
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate a payment_intent.payment_failed event.
     */
    public function paymentIntentFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_FAILED,
            'status' => PaymentStatus::FAILED,
            'error_message' => 'Card declined',
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate a checkout.session.completed event.
     */
    public function requiresAction(): static
    {
        return $this->eventType(PaymentEventType::WEBHOOK_PAYMENT_REQUIRES_ACTION);
    }

    /**
     * Set custom metadata.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], $metadata),
        ]);
    }

    /**
     * Indicate a recent event (within 24 hours).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-23 hours', 'now'),
        ]);
    }

    /**
     * Indicate an old event (older than 24 hours).
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-25 hours'),
        ]);
    }
}
