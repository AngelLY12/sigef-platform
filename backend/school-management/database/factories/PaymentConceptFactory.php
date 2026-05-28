<?php

namespace Database\Factories;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Models\PaymentConcept;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentConcept>
 */
class PaymentConceptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentConcept::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Nombres de conceptos de pago comunes
        $conceptNames = [
            'Inscripción Semestral',
            'Colegiatura Mensual',
            'Reinscripción',
            'Examen de Admisión',
            'Derecho a Titulación',
            'Material Didáctico',
            'Actividades Extraescolares',
            'Seguro Estudiantil',
            'Cuota de Biblioteca',
            'Laboratorio de Ciencias',
            'Laboratorio de Computación',
            'Taller de Inglés',
            'Excursión Educativa',
            'Certificados y Constancias',
            'Cuota de Mantenimiento',
            'Servicio de Transporte',
            'Uniformes',
            'Curso de Verano',
            'Seminario Especializado',
            'Congreso Académico',
        ];

        // Descripciones de conceptos
        $descriptions = [
            'Pago correspondiente a la inscripción del semestre',
            'Mensualidad por servicios educativos',
            'Costo por reinscripción al siguiente semestre',
            'Derecho a presentar examen de admisión',
            'Tramitación y expedición de título profesional',
            'Materiales necesarios para el curso',
            'Participación en actividades deportivas y culturales',
            'Seguro médico estudiantil por accidentes',
            'Uso de instalaciones y recursos bibliotecarios',
            'Uso de equipo y materiales de laboratorio',
            'Acceso a equipos de cómputo y software',
            'Curso intensivo de inglés',
            'Viaje educativo programado',
            'Expedición de documentos oficiales',
            'Mantenimiento de instalaciones educativas',
            'Servicio de transporte escolar',
            'Vestimenta oficial de la institución',
            'Programa académico de verano',
            'Seminario de especialización profesional',
            'Participación en congreso académico',
        ];

        // Fechas de vigencia
        $startDate = $this->faker->dateTimeBetween('-6 months', '+1 month');
        $endDate = $this->faker->optional(0.7)->dateTimeBetween(
            $startDate->format('Y-m-d') . ' +1 month',
            $startDate->format('Y-m-d') . ' +12 months'
        );

        return [
            'concept_name' => $this->faker->randomElement($conceptNames),
            'description' => $this->faker->optional(0.9)->randomElement($descriptions), // 90% tienen descripción
            'status' => $this->faker->randomElement(PaymentConceptStatus::cases()),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
            'applies_to' => $this->faker->randomElement(PaymentConceptAppliesTo::cases()),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }

    /**
     * Indicate that the payment concept is active.
     */
    public function active(): static
    {
        $startDate = $this->faker->dateTimeBetween('-3 months', '+1 month');

        return $this->state(fn (array $attributes) => [
            'status' => PaymentConceptStatus::ACTIVO,
            'start_date' => $startDate,
            'end_date' => $this->faker->optional(0.8)->dateTimeBetween(
                $startDate->format('Y-m-d') . ' +3 months',
                $startDate->format('Y-m-d') . ' +12 months'
            ),
        ]);
    }

    /**
     * Indicate that the payment concept is inactive.
     */
    public function inactive(): static
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', '-1 year');

        return $this->state(fn (array $attributes) => [
            'status' => PaymentConceptStatus::DESACTIVADO,
            'start_date' => $startDate,
            'end_date' => $this->faker->optional(0.9)->dateTimeBetween(
                $startDate->format('Y-m-d') . ' +1 month',
                $startDate->format('Y-m-d') . ' +6 months'
            ),
        ]);
    }

    /**
     * Indicate that the payment concept is upcoming.
     */
    public function upcoming(): static
    {
        $startDate = $this->faker->dateTimeBetween('+1 week', '+3 months');

        return $this->state(fn (array $attributes) => [
            'status' => PaymentConceptStatus::ACTIVO,
            'start_date' => $startDate,
            'end_date' => $this->faker->optional(0.7)->dateTimeBetween(
                $startDate->format('Y-m-d') . ' +3 months',
                $startDate->format('Y-m-d') . ' +12 months'
            ),
        ]);
    }

    /**
     * Indicate that the payment concept is expired.
     */
    public function expired(): static
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', '-1 year');
        $endDate = $this->faker->dateTimeBetween(
            $startDate->format('Y-m-d') . ' +1 month',
            $startDate->format('Y-m-d') . ' +6 months'
        );

        return $this->state(fn (array $attributes) => [
            'status' => PaymentConceptStatus::FINALIZADO,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Indicate that the payment concept applies to all students.
     */
    public function appliesToAll(): static
    {
        return $this->state(fn (array $attributes) => [
            'applies_to' => PaymentConceptAppliesTo::TODOS,
        ]);
    }

    /**
     * Indicate that the payment concept applies to specific careers.
     */
    public function appliesToCareers(): static
    {
        return $this->state(fn (array $attributes) => [
            'applies_to' => PaymentConceptAppliesTo::CARRERA,
        ]);
    }

    /**
     * Indicate that the payment concept applies to specific users.
     */
    public function appliesToUsers(): static
    {
        return $this->state(fn (array $attributes) => [
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES,
        ]);
    }

    /**
     * Indicate that the payment concept is for tuition.
     */
    public function tuition(): static
    {
        return $this->state(fn (array $attributes) => [
            'concept_name' => 'Colegiatura Mensual',
            'description' => 'Mensualidad por servicios educativos del mes',
            'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
            'status' => PaymentConceptStatus::ACTIVO,
        ]);
    }

    /**
     * Indicate that the payment concept is for enrollment.
     */
    public function enrollment(): static
    {
        return $this->state(fn (array $attributes) => [
            'concept_name' => 'Inscripción Semestral',
            'description' => 'Costo de inscripción para el semestre académico',
            'amount' => $this->faker->randomFloat(2, 3000, 10000),
            'status' => PaymentConceptStatus::ACTIVO,
        ]);
    }

    /**
     * Indicate that the payment concept is for exam.
     */
    public function exam(): static
    {
        return $this->faker->randomElement([
            fn () => $this->state([
                'concept_name' => 'Examen de Admisión',
                'description' => 'Derecho a presentar examen de admisión',
                'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
            ]),
            fn () => $this->state([
                'concept_name' => 'Examen Extraordinario',
                'description' => 'Costo por presentar examen extraordinario',
                'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
            ]),
            fn () => $this->state([
                'concept_name' => 'Examen de Titulación',
                'description' => 'Derecho a presentar examen de titulación',
                'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
            ]),
        ]);
    }

    /**
     * Indicate that the payment concept is for materials.
     */
    public function materials(): static
    {
        return $this->state(fn (array $attributes) => [
            'concept_name' => 'Material Didáctico',
            'description' => 'Materiales necesarios para el desarrollo del curso',
            'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
            'status' => PaymentConceptStatus::ACTIVO,
        ]);
    }

    /**
     * Indicate that the payment concept is for certification.
     */
    public function certification(): static
    {
        return $this->state(fn (array $attributes) => [
            'concept_name' => 'Derecho a Titulación',
            'description' => 'Tramitación y expedición de título profesional',
            'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
            'status' => PaymentConceptStatus::ACTIVO,
        ]);
    }

    /**
     * Indicate that the payment concept has a specific amount.
     */
    public function amount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
        ]);
    }

    /**
     * Indicate that the payment concept has a specific date range.
     */
    public function dateRange(string $startDate, ?string $endDate = null): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Indicate that the payment concept is for the current semester.
     */
    public function currentSemester(): static
    {
        $now = now();
        $semesterStart = $now->month <= 6
            ? $now->copy()->startOfYear() // Primer semestre: enero-junio
            : $now->copy()->month(7)->startOfMonth(); // Segundo semestre: julio-diciembre

        $semesterEnd = $now->month <= 6
            ? $now->copy()->month(6)->endOfMonth()
            : $now->copy()->endOfYear();

        return $this->state(fn (array $attributes) => [
            'start_date' => $semesterStart,
            'end_date' => $semesterEnd,
            'status' => PaymentConceptStatus::ACTIVO,
        ]);
    }

    /**
     * Indicate that the payment concept is for the next semester.
     */
    public function nextSemester(): static
    {
        $nextSemesterStart = now()->month <= 6
            ? now()->copy()->month(7)->startOfMonth() // Julio si estamos en primer semestre
            : now()->copy()->addYear()->startOfYear(); // Enero del próximo año si estamos en segundo

        $nextSemesterEnd = $nextSemesterStart->month === 7
            ? $nextSemesterStart->copy()->endOfYear()
            : $nextSemesterStart->copy()->month(6)->endOfMonth();

        return $this->state(fn (array $attributes) => [
            'start_date' => $nextSemesterStart,
            'end_date' => $nextSemesterEnd,
            'status' => PaymentConceptStatus::ACTIVO,
        ]);
    }

    /**
     * Indicate that the payment concept has no end date (permanent).
     */
    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => null,
            'status' => PaymentConceptStatus::ACTIVO,
        ]);
    }

    /**
     * Indicate that the payment concept is for a workshop.
     */
    public function workshop(): static
    {
        $workshops = [
            'Taller de Inglés',
            'Taller de Programación',
            'Taller de Robótica',
            'Taller de Matemáticas',
            'Taller de Ciencias',
            'Taller de Arte',
            'Taller de Música',
        ];

        return $this->state(fn (array $attributes) => [
            'concept_name' => $this->faker->randomElement($workshops),
            'description' => 'Taller especializado de ' . $this->faker->word(),
            'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
            'applies_to' => PaymentConceptAppliesTo::CARRERA, // Talleres suelen ser por carrera
        ]);
    }

    /**
     * Indicate that the payment concept is for transportation.
     */
    public function transportation(): static
    {
        return $this->state(fn (array $attributes) => [
            'concept_name' => 'Servicio de Transporte',
            'description' => 'Transporte escolar mensual',
            'amount' => number_format($this->faker->randomFloat(2, 100, 10000), 2, '.', ''),
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES, // Transporte es por usuario
        ]);
    }

    /**
     * Indicate that the payment concept was created recently.
     */
    public function recentlyCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'updated_at' => fn (array $attrs) => $attrs['created_at'],
        ]);
    }

    /**
     * Create multiple payment concepts for different scenarios.
     */
    public function createCommonConcepts(): array
    {
        $concepts = [];

        // Conceptos básicos que toda institución tiene
        $commonConcepts = [
            [
                'factory' => fn () => $this->enrollment()->active()->currentSemester(),
                'count' => 1,
            ],
            [
                'factory' => fn () => $this->tuition()->active()->permanent(),
                'count' => 1,
            ],
            [
                'factory' => fn () => $this->exam()->active(),
                'count' => 2,
            ],
            [
                'factory' => fn () => $this->materials()->active(),
                'count' => 3,
            ],
            [
                'factory' => fn () => $this->certification()->active(),
                'count' => 1,
            ],
            [
                'factory' => fn () => $this->workshop()->active(),
                'count' => 4,
            ],
        ];

        foreach ($commonConcepts as $conceptConfig) {
            for ($i = 0; $i < $conceptConfig['count']; $i++) {
                $concepts[] = $conceptConfig['factory']()->create();
            }
        }

        return $concepts;
    }
}
