<?php

namespace Database\Factories;

use App\Models\PaymentConceptUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\PaymentConcept;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppModelsPaymentConceptUser>
 */
class PaymentConceptUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentConceptUser::class;

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
     * Indicate that the relation is for a specific payment concept.
     */
    public function forPaymentConcept(PaymentConcept $concept): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_concept_id' => $concept->id,
        ]);
    }

    /**
     * Indicate that the relation is for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the relation was created recently.
     */
    public function recentlyCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'updated_at' => fn (array $attrs) => $attrs['created_at'],
        ]);
    }

    /**
     * Create multiple user assignments for a payment concept.
     */
    public function assignUsersToConcept(PaymentConcept $concept, array $userIds): void
    {
        foreach ($userIds as $userId) {
            PaymentConceptUser::factory()
                ->forPaymentConcept($concept)
                ->forUser(User::find($userId))
                ->create();
        }
    }

    /**
     * Create multiple concept assignments for a user.
     */
    public function assignConceptsToUser(User $user, array $conceptIds): void
    {
        foreach ($conceptIds as $conceptId) {
            PaymentConceptUser::factory()
                ->forPaymentConcept(PaymentConcept::find($conceptId))
                ->forUser($user)
                ->create();
        }
    }
}
