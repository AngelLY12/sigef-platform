<?php

namespace Database\Factories;

use App\Models\Career;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentDetail>
 */
class StudentDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentDetail::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        // Semestres comunes (1-12)
        $semestres = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

        // Grupos comunes (A, B, C, D, etc.)
        $groups = ['A', 'B', 'C', 'D', 'E', 'F'];

        // Talleres comunes
        $workshops = [
            'Taller de Lectura y Redacción',
            'Taller de Matemáticas',
            'Taller de Física',
            'Taller de Química',
            'Taller de Informática',
            'Taller de Inglés',
            'Taller de Investigación',
            'Taller de Ética',
            null,
        ];

        return [
            'user_id' => User::factory(),
            'career_id' => Career::factory(),
            'n_control' => str_pad(rand(1, 99999), 8, '0', STR_PAD_LEFT),
            'semestre' => $this->faker->randomElement($semestres),
            'group' => $this->faker->optional(0.8)->randomElement($groups), // 80% tienen grupo
            'workshop' => $this->faker->optional(0.6)->randomElement($workshops), // 60% tienen taller
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the student detail belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the student detail belongs to a specific career.
     */
    public function forCareer(Career $career): static
    {
        return $this->state(fn (array $attributes) => [
            'career_id' => $career->id,
        ]);
    }

    /**
     * Indicate that the student is in first semester.
     */
    public function firstSemester(): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => 1,
            'group' => $this->faker->randomElement(['A', 'B', 'C']),
        ]);
    }

    /**
     * Indicate that the student is in intermediate semester (3-6).
     */
    public function intermediateSemester(): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => $this->faker->numberBetween(3, 6),
        ]);
    }

    /**
     * Indicate that the student is in final semester (7-12).
     */
    public function finalSemester(): static
    {
        return $this->state(fn (array $attributes) => [
            'semestre' => $this->faker->numberBetween(7, 12),
        ]);
    }

    /**
     * Indicate that the student has no career assigned.
     */
    public function withoutCareer(): static
    {
        return $this->state(fn (array $attributes) => [
            'career_id' => null,
        ]);
    }

    /**
     * Indicate that the student is in a specific group.
     */
    public function group(string $group): static
    {
        return $this->state(fn (array $attributes) => [
            'group' => $group,
        ]);
    }

    /**
     * Indicate that the student is in a specific workshop.
     */
    public function workshop(string $workshop): static
    {
        return $this->state(fn (array $attributes) => [
            'workshop' => $workshop,
        ]);
    }

    /**
     * Indicate that the student has no number control (for new students).
     */
    public function withoutControlNumber(): static
    {
        return $this->state(fn (array $attributes) => [
            'n_control' => null,
        ]);
    }
}
