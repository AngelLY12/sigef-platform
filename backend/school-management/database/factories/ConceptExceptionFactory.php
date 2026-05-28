<?php

namespace Database\Factories;

use App\Models\ConceptException;
use App\Models\PaymentConcept;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConceptException>
 */
class ConceptExceptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConceptException::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_concept_id' => PaymentConcept::factory(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the exception is for a specific payment concept.
     */
    public function forPaymentConcept(PaymentConcept $concept): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_concept_id' => $concept->id,
        ]);
    }

    /**
     * Indicate that the exception is for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the exception was created recently.
     */
    public function recentlyCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'updated_at' => fn (array $attrs) => $attrs['created_at'],
        ]);
    }

    /**
     * Indicate that the exception is for a scholarship student.
     */
    public function forScholarship(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Indicate that the exception is for a special case.
     */
    public function specialCase(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
        ]);
    }

    /**
     * Create multiple exceptions for a payment concept.
     */
    public function assignExceptionsToConcept(PaymentConcept $concept, array $userIds): void
    {
        foreach ($userIds as $userId) {
            ConceptException::factory()
                ->forPaymentConcept($concept)
                ->forUser(User::find($userId))
                ->create();
        }
    }

    /**
     * Create multiple exceptions for a user.
     */
    public function assignExceptionsToUser(User $user, array $conceptIds): void
    {
        foreach ($conceptIds as $conceptId) {
            ConceptException::factory()
                ->forPaymentConcept(PaymentConcept::find($conceptId))
                ->forUser($user)
                ->create();
        }
    }
}
