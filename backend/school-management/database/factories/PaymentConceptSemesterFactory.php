<?php

namespace Database\Factories;

use App\Models\PaymentConceptSemester;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PaymentConcept;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppModelsPaymentConceptSemester>
 */
class PaymentConceptSemesterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentConceptSemester::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $semestres = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

        return [
            'payment_concept_id' => PaymentConcept::factory(),
            'semestre' => $this->faker->randomElement($semestres),
        ];
    }

    /**
     * Indicate that the semester assignment is for a specific payment concept.
     */
    public function forPaymentConcept(PaymentConcept $concept): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_concept_id' => $concept->id,
        ]);
    }

    /**
     * Indicate that the payment concept applies to a specific semester.
     */
    public function forSemester(int $semester): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => $semester,
        ]);
    }

    /**
     * Indicate that the payment concept applies to first semester students.
     */
    public function firstSemester(): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => 1,
        ]);
    }

    /**
     * Indicate that the payment concept applies to intermediate semesters (2-6).
     */
    public function intermediateSemesters(): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => $this->faker->numberBetween(2, 6),
        ]);
    }

    /**
     * Indicate that the payment concept applies to final semesters (7-12).
     */
    public function finalSemesters(): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => $this->faker->numberBetween(7, 12),
        ]);
    }

    /**
     * Indicate that the payment concept applies to all semesters.
     */
    public function allSemesters(): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => $this->faker->randomElement([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]),
        ]);
    }

    /**
     * Create multiple semester assignments for a payment concept.
     */
    public function assignSemestersToConcept(PaymentConcept $concept, array $semesters): void
    {
        foreach ($semesters as $semester) {
            PaymentConceptSemester::factory()
                ->forPaymentConcept($concept)
                ->forSemester($semester)
                ->create();
        }
    }
}
