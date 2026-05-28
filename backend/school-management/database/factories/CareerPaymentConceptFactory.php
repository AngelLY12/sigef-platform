<?php

namespace Database\Factories;

use App\Models\CareerPaymentConcept;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Career;
use App\Models\PaymentConcept;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CareerPaymentConcept>
 */
class CareerPaymentConceptFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CareerPaymentConcept::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'career_id' => Career::factory(),
            'payment_concept_id' => PaymentConcept::factory(),
        ];
    }

    /**
     * Indicate that the relation is for a specific career.
     */
    public function forCareer(Career $career): static
    {
        return $this->state(fn (array $attributes) => [
            'career_id' => $career->id,
        ]);
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
     * Create multiple career assignments for a payment concept.
     */
    public function assignCareersToConcept(PaymentConcept $concept, array $careerIds): void
    {
        foreach ($careerIds as $careerId) {
            CareerPaymentConcept::factory()
                ->forCareer(Career::find($careerId))
                ->forPaymentConcept($concept)
                ->create();
        }
    }

    /**
     * Create multiple concept assignments for a career.
     */
    public function assignConceptsToCareer(Career $career, array $conceptIds): void
    {
        foreach ($conceptIds as $conceptId) {
            CareerPaymentConcept::factory()
                ->forCareer($career)
                ->forPaymentConcept(PaymentConcept::find($conceptId))
                ->create();
        }
    }

    /**
     * Indicate that the payment concept is for engineering careers.
     */
    public function forEngineeringCareers(): static
    {
        return $this->state(function (array $attributes) {
            $engineeringCareers = Career::where('name', 'like', '%ingenier%')->get();

            if ($engineeringCareers->isEmpty()) {
                $engineeringCareers = collect([
                    Career::factory()->create(['name' => 'Ingeniería en Sistemas']),
                    Career::factory()->create(['name' => 'Ingeniería Industrial']),
                ]);
            }

            return [
                'career_id' => $engineeringCareers->random()->id,
            ];
        });
    }

    /**
     * Indicate that the payment concept is for administrative careers.
     */
    public function forAdministrativeCareers(): static
    {
        return $this->state(function (array $attributes) {
            $adminCareers = Career::where('name', 'like', '%administra%')->get();

            if ($adminCareers->isEmpty()) {
                $adminCareers = collect([
                    Career::factory()->create(['name' => 'Administración de Empresas']),
                    Career::factory()->create(['name' => 'Contaduría']),
                ]);
            }

            return [
                'career_id' => $adminCareers->random()->id,
            ];
        });
    }
}
