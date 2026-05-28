<?php

namespace Database\Factories;

use App\Models\Career;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Career>
 */
class CareerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Career::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Carreras comunes en instituciones educativas
        $careers = [
            'Ingeniería en Sistemas Computacionales',
            'Ingeniería Industrial',
            'Ingeniería Mecánica',
            'Ingeniería Civil',
            'Ingeniería Electrónica',
            'Ingeniería en Telecomunicaciones',
            'Administración de Empresas',
            'Contaduría Pública',
            'Finanzas',
            'Mercadotecnia',
            'Derecho',
            'Medicina',
            'Enfermería',
            'Psicología',
            'Arquitectura',
            'Diseño Gráfico',
            'Comunicación',
            'Turismo',
            'Gastronomía',
            'Educación',
        ];

        $careerName = $this->faker->unique()->randomElement($careers);

        return [
            'career_name' => $careerName,
        ];
    }

    /**
     * Indicate that the career is in engineering.
     */
    public function engineering(): static
    {
        $engineeringCareers = [
            'Ingeniería en Sistemas Computacionales',
            'Ingeniería Industrial',
            'Ingeniería Mecánica',
            'Ingeniería Civil',
            'Ingeniería Electrónica',
            'Ingeniería en Telecomunicaciones',
            'Ingeniería Química',
            'Ingeniería en Mecatrónica',
            'Ingeniería en Software',
        ];

        return $this->state(fn (array $attributes) => [
            'career_name' => $this->faker->unique()->randomElement($engineeringCareers),
        ]);
    }

    /**
     * Indicate that the career is in business/administration.
     */
    public function business(): static
    {
        $businessCareers = [
            'Administración de Empresas',
            'Contaduría Pública',
            'Finanzas',
            'Mercadotecnia',
            'Negocios Internacionales',
            'Recursos Humanos',
            'Comercio Exterior',
        ];

        return $this->state(fn (array $attributes) => [
            'career_name' => $this->faker->unique()->randomElement($businessCareers),
        ]);
    }

    /**
     * Indicate that the career is in health sciences.
     */
    public function healthSciences(): static
    {
        $healthCareers = [
            'Medicina',
            'Enfermería',
            'Odontología',
            'Nutrición',
            'Fisioterapia',
            'Psicología Clínica',
            'Farmacia',
        ];

        return $this->state(fn (array $attributes) => [
            'career_name' => $this->faker->unique()->randomElement($healthCareers),
        ]);
    }

    /**
     * Indicate that the career is in social sciences.
     */
    public function socialSciences(): static
    {
        $socialCareers = [
            'Derecho',
            'Psicología',
            'Sociología',
            'Antropología',
            'Ciencias Políticas',
            'Relaciones Internacionales',
            'Trabajo Social',
        ];

        return $this->state(fn (array $attributes) => [
            'career_name' => $this->faker->unique()->randomElement($socialCareers),
        ]);
    }

    /**
     * Indicate that the career is in arts and design.
     */
    public function artsAndDesign(): static
    {
        $artsCareers = [
            'Arquitectura',
            'Diseño Gráfico',
            'Diseño Industrial',
            'Artes Visuales',
            'Música',
            'Teatro',
            'Danza',
        ];

        return $this->state(fn (array $attributes) => [
            'career_name' => $this->faker->unique()->randomElement($artsCareers),
        ]);
    }

    /**
     * Indicate that the career is in education.
     */
    public function education(): static
    {
        $educationCareers = [
            'Educación Primaria',
            'Educación Secundaria',
            'Educación Especial',
            'Pedagogía',
            'Ciencias de la Educación',
            'Educación Física',
        ];

        return $this->state(fn (array $attributes) => [
            'career_name' => $this->faker->unique()->randomElement($educationCareers),
        ]);
    }

    /**
     * Indicate that the career has a specific name.
     */
    public function name(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'career_name' => $name,
        ]);
    }

    /**
     * Indicate that the career was created recently.
     */
    public function recentlyCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => fn (array $attrs) => $attrs['created_at'],
        ]);
    }

    /**
     * Indicate that the career is old/established.
     */
    public function established(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-10 years', '-5 years'),
            'updated_at' => fn (array $attrs) => $attrs['created_at'],
        ]);
    }

    /**
     * Create a set of common careers for an institution.
     */
    public function createCommonCareers(): array
    {
        $commonCareers = [
            'Ingeniería en Sistemas Computacionales',
            'Administración de Empresas',
            'Contaduría Pública',
            'Derecho',
            'Psicología',
            'Arquitectura',
            'Mercadotecnia',
            'Comunicación',
            'Turismo',
            'Gastronomía',
        ];

        $careers = [];

        foreach ($commonCareers as $careerName) {
            $careers[] = Career::factory()
                ->name($careerName)
                ->create();
        }

        return $careers;
    }

    /**
     * Create careers with students enrolled.
     */
    public function withStudents(int $studentCount = 10): static
    {
        return $this->afterCreating(function (Career $career) use ($studentCount) {
            // Crear usuarios estudiantes para esta carrera
            for ($i = 0; $i < $studentCount; $i++) {
                $user = \App\Models\User::factory()
                    ->create();

                // Crear student detail con esta carrera
                \App\Models\StudentDetail::factory()
                    ->forUser($user)
                    ->forCareer($career)
                    ->create();
            }
        });
    }

    /**
     * Create careers with payment concepts.
     */
    public function withPaymentConcepts(int $conceptCount = 5): static
    {
        return $this->afterCreating(function (Career $career) use ($conceptCount) {
            // Crear conceptos de pago para esta carrera
            $concepts = \App\Models\PaymentConcept::factory()
                ->count($conceptCount)
                ->appliesToCareers()
                ->create();

            // Asignar conceptos a la carrera
            foreach ($concepts as $concept) {
                \App\Models\CareerPaymentConcept::factory()
                    ->forCareer($career)
                    ->forPaymentConcept($concept)
                    ->create();
            }
        });
    }
}
