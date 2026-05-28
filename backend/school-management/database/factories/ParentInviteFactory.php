<?php

namespace Database\Factories;

use App\Models\ParentInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParentInvite>
 */
class ParentInviteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ParentInvite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $student = User::factory()->create();
        $creator = User::factory()->create();

        return [
            'student_id' => $student->id,
            'email' => $this->faker->safeEmail(),
            'token' => Str::uuid()->toString(),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'used_at' => null,
            'created_by' => $creator->id,
        ];
    }

    /**
     * Indicate that the invite is for a specific student.
     */
    public function forStudent(User $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
        ]);
    }

    /**
     * Indicate that the invite was created by a specific user.
     */
    public function createdBy(User $creator): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $creator->id,
        ]);
    }

    /**
     * Indicate that the invite is active (not expired, not used).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+7 days'),
            'used_at' => null,
        ]);
    }

    /**
     * Indicate that the invite is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'used_at' => null,
        ]);
    }

    /**
     * Indicate that the invite has been used.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('-60 days', '-31 days'),
        ]);
    }

    /**
     * Indicate that the invite is about to expire.
     */
    public function aboutToExpire(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+12 hours'),
            'used_at' => null,
        ]);
    }

    /**
     * Indicate that the invite has a specific email.
     */
    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Indicate that the invite has a specific token.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token' => $token,
        ]);
    }

    /**
     * Indicate that the invite was created recently.
     */
    public function recentlyCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+7 days'),
            'updated_at' => fn (array $attrs) => $attrs['created_at'],
        ]);
    }

    /**
     * Indicate that the invite is for a parent email.
     */
    public function forParentEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $this->faker->safeEmail(),
        ]);
    }

    /**
     * Create multiple invites for a student.
     */
    public function multipleForStudent(User $student, int $count = 3): array
    {
        $invites = [];

        for ($i = 0; $i < $count; $i++) {
            $invites[] = ParentInvite::factory()
                ->forStudent($student)
                ->state([
                    'email' => $this->faker->unique()->safeEmail(),
                    'token' => Str::uuid()->toString(),
                    'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
                    'used_at' => $i === 0 ? $this->faker->dateTimeBetween('-10 days', 'now') : null,
                ])
                ->create();
        }

        return $invites;
    }

    /**
     * Create invites for multiple students.
     */
    public function invitesForStudents(array $students, int $perStudent = 2): array
    {
        $invites = [];

        foreach ($students as $student) {
            for ($i = 0; $i < $perStudent; $i++) {
                $invites[] = ParentInvite::factory()
                    ->forStudent($student)
                    ->state([
                        'email' => $this->faker->safeEmail(),
                        'token' => Str::uuid()->toString(),
                        'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
                    ])
                    ->create();
            }
        }

        return $invites;
    }
}
