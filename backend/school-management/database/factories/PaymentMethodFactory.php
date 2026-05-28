<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Marcas de tarjetas comunes
        $brands = ['Visa', 'MasterCard', 'American Express', 'Discover'];

        // Generar fecha de expiración futura
        $expMonth = $this->faker->numberBetween(1, 12);
        $expYear = $this->faker->numberBetween(date('Y') + 1, date('Y') + 5);

        return [
            'user_id' => User::factory(),
            'stripe_payment_method_id' => 'pm_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'brand' => $this->faker->randomElement($brands),
            'last4' => $this->faker->numerify('####'),
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }

    /**
     * Indicate that the payment method belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the payment method is a Visa card.
     */
    public function visa(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => 'Visa',
            'last4' => $this->faker->numerify('4###'), // Visa normalmente empieza con 4
            'stripe_payment_method_id' => 'pm_visa_' . $this->faker->unique()->regexify('[A-Za-z0-9]{20}'),
        ]);
    }

    /**
     * Indicate that the payment method is a MasterCard.
     */
    public function mastercard(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => 'MasterCard',
            'last4' => $this->faker->numerify('5###'), // MasterCard normalmente empieza con 5
            'stripe_payment_method_id' => 'pm_mc_' . $this->faker->unique()->regexify('[A-Za-z0-9]{20}'),
        ]);
    }

    /**
     * Indicate that the payment method is an American Express.
     */
    public function americanExpress(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => 'American Express',
            'last4' => $this->faker->numerify('3###'), // Amex normalmente empieza con 3
            'stripe_payment_method_id' => 'pm_amex_' . $this->faker->unique()->regexify('[A-Za-z0-9]{20}'),
        ]);
    }

    /**
     * Indicate that the payment method is expired.
     */
    public function expired(): static
    {
        $expMonth = $this->faker->numberBetween(1, 12);
        $expYear = $this->faker->numberBetween(date('Y') - 2, date('Y') - 1);

        return $this->state(fn (array $attributes) => [
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
        ]);
    }

    /**
     * Indicate that the payment method is about to expire (este mes o el próximo).
     */
    public function aboutToExpire(): static
    {
        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');

        // 50% este mes, 50% próximo mes
        if ($this->faker->boolean()) {
            $expMonth = $currentMonth;
            $expYear = $currentYear;
        } else {
            $expMonth = $currentMonth + 1;
            $expYear = $expMonth > 12 ? $currentYear + 1 : $currentYear;
            $expMonth = $expMonth > 12 ? 1 : $expMonth;
        }

        return $this->state(fn (array $attributes) => [
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
        ]);
    }

    /**
     * Indicate that the payment method expires in a specific month/year.
     */
    public function expiresAt(int $month, int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'exp_month' => $month,
            'exp_year' => $year,
        ]);
    }

    /**
     * Indicate that the payment method has a specific last 4 digits.
     */
    public function withLastFour(string $last4): static
    {
        return $this->state(fn (array $attributes) => [
            'last4' => $last4,
        ]);
    }

    /**
     * Indicate that the payment method has a specific Stripe ID.
     */
    public function withStripeId(string $stripeId): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_payment_method_id' => $stripeId,
        ]);
    }

    /**
     * Indicate that the payment method was created recently.
     */
    public function recentlyAdded(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the payment method is the user's primary/default.
     * Esto sería útil si tu aplicación tiene un campo "is_primary"
     */
    public function primary(): static
    {
        // Si no tienes campo is_primary, esto puede usarse para marcar mentalmente
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-6 months'), // Más antiguo = probablemente primario
        ]);
    }

    /**
     * Indicate that the payment method is for a debit card.
     */
    public function debitCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => $this->faker->randomElement(['Visa', 'MasterCard']),
            'last4' => $this->faker->numerify('4###'),
        ]);
    }

    /**
     * Indicate that the payment method is for a credit card.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => $this->faker->randomElement(['Visa', 'MasterCard', 'American Express', 'Discover']),
            'last4' => $this->faker->numerify($this->faker->randomElement(['4###', '5###', '3###', '6###'])),
        ]);
    }

    /**
     * Create multiple payment methods for the same user.
     */
    public function multipleForUser(User $user, int $count = 2): array
    {
        $paymentMethods = [];

        for ($i = 0; $i < $count; $i++) {
            $paymentMethods[] = PaymentMethod::factory()
                ->forUser($user)
                ->state([
                    'stripe_payment_method_id' => 'pm_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}') . '_' . $i,
                    'last4' => str_pad($i + 1, 4, '0', STR_PAD_LEFT), // Últimos 4 diferentes
                    'exp_month' => $this->faker->numberBetween(1, 12),
                    'exp_year' => $this->faker->numberBetween(date('Y') + 1, date('Y') + 3),
                ])
                ->create();
        }

        return $paymentMethods;
    }

    /**
     * Indicate that the payment method has test data from Stripe.
     */
    public function testStripeCard(): static
    {
        $testCards = [
            [
                'brand' => 'Visa',
                'last4' => '4242',
                'exp_month' => 12,
                'exp_year' => date('Y') + 1,
            ],
            [
                'brand' => 'MasterCard',
                'last4' => '5555',
                'exp_month' => 12,
                'exp_year' => date('Y') + 1,
            ],
            [
                'brand' => 'American Express',
                'last4' => '3782',
                'exp_month' => 12,
                'exp_year' => date('Y') + 1,
            ],
        ];

        $testCard = $this->faker->randomElement($testCards);

        return $this->state(fn (array $attributes) => [
            'brand' => $testCard['brand'],
            'last4' => $testCard['last4'],
            'exp_month' => $testCard['exp_month'],
            'exp_year' => $testCard['exp_year'],
            'stripe_payment_method_id' => 'pm_card_' . strtolower($testCard['brand']) . $this->faker->unique()->regexify('[0-9]{10}'),
        ]);
    }
}
