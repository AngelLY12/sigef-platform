<?php

namespace Database\Factories;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Models\PaymentConcept;
use App\Models\PaymentConceptApplicantTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentConceptApplicantTag>
 */
class PaymentConceptApplicantTagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentConceptApplicantTag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $applicantTypes = PaymentConceptApplicantType::cases();

        return [
            'payment_concept_id' => PaymentConcept::factory(),
            'tag' => $this->faker->randomElement($applicantTypes),
        ];
    }

    /**
     * Indicate that the tag is for a specific payment concept.
     */
    public function forPaymentConcept(PaymentConcept $concept): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_concept_id' => $concept->id,
        ]);
    }


    /**
     * Create multiple applicant tags for a payment concept.
     */
    public function assignTagsToConcept(PaymentConcept $concept, array $tags): void
    {
        foreach ($tags as $tag) {
            PaymentConceptApplicantTag::factory()
                ->forPaymentConcept($concept)
                ->state(['tag' => $tag])
                ->create();
        }
    }
}
