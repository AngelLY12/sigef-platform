<?php

namespace Database\Factories;

use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\PaymentConcept;
use App\Models\PaymentMethod;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Conceptos de pago comunes
        $concepts = [
            'Inscripción Semestral',
            'Colegiatura Mensual',
            'Examen de Admisión',
            'Material Didáctico',
            'Actividad Extraescolar',
            'Seguro Escolar',
            'Cuota de Biblioteca',
            'Laboratorio de Computación',
            'Taller Especializado',
            'Excursión Educativa',
        ];

        // Status de pago
        $statuses = PaymentStatus::cases();

        // Detalles de método de pago comunes
        $paymentMethodDetails = [
            'card' => [
                'brand' => $this->faker->randomElement(['visa', 'mastercard', 'amex']),
                'last4' => $this->faker->numerify('####'),
                'exp_month' => $this->faker->numberBetween(1, 12),
                'exp_year' => $this->faker->numberBetween(date('Y') + 1, date('Y') + 5),
                'country' => 'MX',
            ],
            'card_present' => [
                'brand' => 'visa',
                'last4' => $this->faker->numerify('####'),
                'exp_month' => $this->faker->numberBetween(1, 12),
                'exp_year' => $this->faker->numberBetween(date('Y') + 1, date('Y') + 5),
                'read_method' => $this->faker->randomElement(['contactless', 'swipe', 'insert']),
            ],
        ];

        $concept = $this->faker->randomElement($concepts);
        $amount = $this->faker->randomFloat(2, 500, 10000); // Montos entre $500 y $10,000

        // Para pagos exitosos, amount_received puede ser igual al amount
        // Para pagos parciales, puede ser menor
        $status = $this->faker->randomElement($statuses);
        $amountReceived = $this->calculateAmountReceived($status, $amount);

        return [
            'user_id' => User::factory(),
            'payment_concept_id' => PaymentConcept::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'stripe_payment_method_id' => 'pm_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'concept_name' => $concept,
            'amount' => $amount,
            'amount_received' => $amountReceived,
            'payment_method_details' => $this->faker->randomElement($paymentMethodDetails),
            'status' => $status,
            'payment_intent_id' => 'pi_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'url' => $this->faker->optional(0.3)->url(), // 30% tienen URL
            'stripe_session_id' => function() {
                if ($this->faker->boolean(50)) {
                    return 'cs_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}');
                }
                return null;
            }, // 50% tienen session ID
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }

    /**
     * Calculate amount received based on payment status.
     */
    private function calculateAmountReceived(PaymentStatus $status, float $amount): ?float
    {
        return match($status) {
            PaymentStatus::SUCCEEDED => $amount,
            PaymentStatus::UNDERPAID => $amount * $this->faker->randomFloat(2, 0.1, 0.9), // 10-90% pagado
            PaymentStatus::DEFAULT => $amount * $this->faker->randomFloat(2, 0.5, 1.0), // 50-100% reembolsado
            PaymentStatus::UNPAID => $amount * $this->faker->randomFloat(2, 0.1, 0.5), // 10-50% reembolsado
            default => null,
        };
    }

    /**
     * Indicate that the payment belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the payment is for a specific payment concept.
     */
    public function forConcept(PaymentConcept $concept): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_concept_id' => $concept->id,
            'concept_name' => $concept->name,
        ]);
    }

    /**
     * Indicate that the payment uses a specific payment method.
     */
    public function withPaymentMethod(PaymentMethod $paymentMethod): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_id' => $paymentMethod->id,
            'stripe_payment_method_id' => $paymentMethod->stripe_payment_method_id,
        ]);
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        $amount = $this->faker->randomFloat(2, 500, 10000);

        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::SUCCEEDED,
            'amount' => $amount,
            'amount_received' => $amount,
            'payment_intent_id' => 'pi_completed_' . $this->faker->unique()->regexify('[A-Za-z0-9]{20}'),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => null,
            'payment_intent_id' => 'pi_pending_' . $this->faker->unique()->regexify('[A-Za-z0-9]{20}'),
        ]);
    }

    /**
     * Indicate that the payment is partially paid.
     */
    public function partiallyPaid(): static
    {
        $amount = $this->faker->randomFloat(2, 1000, 8000);
        $amountReceived = $amount * $this->faker->randomFloat(2, 0.1, 0.9);

        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::UNDERPAID,
            'amount' => $amount,
            'amount_received' => $amountReceived,
            'payment_intent_id' => 'pi_partial_' . $this->faker->unique()->regexify('[A-Za-z0-9]{20}'),
        ]);
    }


    /**
     * Indicate that the payment is for tuition.
     */
    public function tuition(): static
    {
        return $this->state(fn (array $attributes) => [
            'concept_name' => 'Colegiatura Mensual',
            'amount' => $this->faker->randomFloat(2, 2000, 8000),
        ]);
    }

    /**
     * Indicate that the payment is for enrollment.
     */
    public function enrollment(): static
    {
        return $this->state(fn (array $attributes) => [
            'concept_name' => 'Inscripción Semestral',
            'amount' => $this->faker->randomFloat(2, 3000, 10000),
        ]);
    }

    /**
     * Indicate that the payment is for materials.
     */
    public function materials(): static
    {
        return $this->state(fn (array $attributes) => [
            'concept_name' => 'Material Didáctico',
            'amount' => $this->faker->randomFloat(2, 500, 2000),
        ]);
    }

    /**
     * Indicate that the payment is for an exam.
     */
    public function exam(): static
    {
        return $this->state(fn (array $attributes) => [
            'concept_name' => 'Examen de Admisión',
            'amount' => $this->faker->randomFloat(2, 800, 1500),
        ]);
    }

    /**
     * Indicate that the payment has a specific amount.
     */
    public function amount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'amount_received' => $attributes['status'] === PaymentStatus::SUCCEEDED ? $amount : $attributes['amount_received'],
        ]);
    }

    /**
     * Indicate that the payment was made recently (last 7 days).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the payment was made in a specific month.
     */
    public function inMonth(int $year, int $month): static
    {
        $date = \Carbon\Carbon::create($year, $month, $this->faker->numberBetween(1, 28));

        return $this->state(fn (array $attributes) => [
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    /**
     * Indicate that the payment has a payment URL (for pending payments).
     */
    public function withUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => $this->faker->url(),
            'stripe_session_id' => 'cs_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
        ]);
    }

    /**
     * Indicate that the payment was made with card present (in-person).
     */
    public function cardPresent(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_details' => [
                'card_present' => [
                    'brand' => 'visa',
                    'last4' => $this->faker->numerify('####'),
                    'exp_month' => $this->faker->numberBetween(1, 12),
                    'exp_year' => $this->faker->numberBetween(date('Y') + 1, date('Y') + 5),
                    'read_method' => $this->faker->randomElement(['contactless', 'swipe', 'insert']),
                ]
            ],
        ]);
    }

    /**
     * Indicate that the payment was made online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_details' => [
                'card' => [
                    'brand' => $this->faker->randomElement(['visa', 'mastercard', 'amex']),
                    'last4' => $this->faker->numerify('####'),
                    'exp_month' => $this->faker->numberBetween(1, 12),
                    'exp_year' => $this->faker->numberBetween(date('Y') + 1, date('Y') + 5),
                    'country' => 'MX',
                ]
            ],
            'url' => $this->faker->optional(0.7)->url(),
        ]);
    }

    /**
     * Create multiple payments for the same user.
     */
    public function multipleForUser(User $user, int $count = 3): array
    {
        $payments = [];
        $statuses = [PaymentStatus::SUCCEEDED, PaymentStatus::DEFAULT, PaymentStatus::UNDERPAID];

        for ($i = 0; $i < $count; $i++) {
            $payments[] = Payment::factory()
                ->forUser($user)
                ->state([
                    'concept_name' => $this->faker->randomElement([
                        'Colegiatura Mes ' . $this->faker->monthName(),
                        'Inscripción Semestral',
                        'Material Didáctico',
                        'Actividad Extraescolar',
                    ]),
                    'amount' => $this->faker->randomFloat(2, 1000, 5000),
                    'status' => $this->faker->randomElement($statuses),
                    'payment_intent_id' => 'pi_user_' . $user->id . '_' . $i . '_' . $this->faker->regexify('[A-Za-z0-9]{20}'),
                ])
                ->create();
        }

        return $payments;
    }
}
