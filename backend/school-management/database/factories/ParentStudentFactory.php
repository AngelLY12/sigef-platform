<?php

namespace Database\Factories;

use Spatie\Permission\Models\Role;
use App\Core\Domain\Enum\User\RelationshipType;
use App\Core\Domain\Enum\User\UserRoles;
use App\Models\ParentStudent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParentStudent>
 */
class ParentStudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ParentStudent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Crear o encontrar roles usando los valores correctos del enum
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first() ??
            Role::create(['name' => UserRoles::PARENT->value, 'guard_name' => 'web']);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first() ??
            Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'web']);

        // Crear usuarios padre y estudiante
        $parent = User::factory()
            ->afterCreating(function (User $user) use ($parentRole) {
                $user->assignRole($parentRole);
            })
            ->create();

        $student = User::factory()
            ->state([
                'birthdate' => $this->faker->dateTimeBetween('-25 years', '-15 years')->format('Y-m-d'),
            ])
            ->afterCreating(function (User $user) use ($studentRole) {
                $user->assignRole($studentRole);
            })
            ->create();

        return [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => $this->faker->randomElement([
                RelationshipType::PADRE,
                RelationshipType::MADRE,
                RelationshipType::TUTOR,
                RelationshipType::TUTOR_LEGAL,
            ]),
        ];
    }

    /**
     * Indicate that the parent is the father.
     */
    public function father(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => RelationshipType::PADRE,
        ]);
    }

    /**
     * Indicate that the parent is the mother.
     */
    public function mother(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => RelationshipType::MADRE,
        ]);
    }

    /**
     * Indicate that the parent is a guardian.
     */
    public function guardian(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => RelationshipType::TUTOR,
        ]);
    }

    /**
     * Indicate that the parent is a sibling.
     */
    public function sibling(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => RelationshipType::TUTOR, // O crear HERMANO si es necesario
        ]);
    }

    /**
     * Indicate that the relation is for a specific parent.
     */
    public function forParent(User $parent): static
    {
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first() ??
            Role::create(['name' => UserRoles::PARENT->value, 'guard_name' => 'web']);

        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'parent_role_id' => $parentRole->id,
        ]);
    }

    /**
     * Indicate that the relation is for a specific student.
     */
    public function forStudent(User $student): static
    {
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first() ??
            Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'web']);

        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
            'student_role_id' => $studentRole->id,
        ]);
    }

    /**
     * Indicate that the parent has multiple students.
     */
    public function parentWithMultipleStudents(User $parent, int $studentCount = 2): array
    {
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first() ??
            Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'web']);

        $relations = [];

        for ($i = 0; $i < $studentCount; $i++) {
            $student = User::factory()
                ->state([
                    'birthdate' => $this->faker->dateTimeBetween('-25 years', '-15 years')->format('Y-m-d'),
                ])
                ->afterCreating(fn (User $user) => $user->assignRole($studentRole))
                ->create();

            $relations[] = ParentStudent::factory()
                ->forParent($parent)
                ->forStudent($student)
                ->state([
                    'relationship' => $this->faker->randomElement([
                        RelationshipType::PADRE,
                        RelationshipType::MADRE,
                    ]),
                ])
                ->create();
        }

        return $relations;
    }

    /**
     * Indicate that the student has multiple parents.
     */
    public function studentWithMultipleParents(User $student, int $parentCount = 2): array
    {
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first() ??
            Role::create(['name' => UserRoles::PARENT->value, 'guard_name' => 'web']);

        $relations = [];

        for ($i = 0; $i < $parentCount; $i++) {
            $parent = User::factory()
                ->state([
                    'birthdate' => $this->faker->dateTimeBetween('-60 years', '-30 years')->format('Y-m-d'),
                ])
                ->afterCreating(fn (User $user) => $user->assignRole($parentRole))
                ->create();

            $relations[] = ParentStudent::factory()
                ->forParent($parent)
                ->forStudent($student)
                ->state([
                    'relationship' => $i === 0 ? RelationshipType::PADRE : RelationshipType::MADRE,
                ])
                ->create();
        }

        return $relations;
    }

    /**
     * Indicate that the relation was created recently.
     */
    public function recentlyCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ]);
    }

    /**
     * Create a family with parents and students.
     */
    public function createFamily(int $parentCount = 2, int $studentCount = 2): array
    {
        $family = [];

        // Crear padres
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first() ??
            Role::create(['name' => UserRoles::PARENT->value, 'guard_name' => 'web']);

        $parents = [];
        for ($i = 0; $i < $parentCount; $i++) {
            $parents[] = User::factory()
                ->state([
                    'birthdate' => $this->faker->dateTimeBetween('-60 years', '-30 years')->format('Y-m-d'),
                ])
                ->afterCreating(fn (User $user) => $user->assignRole($parentRole))
                ->create();
        }

        // Crear estudiantes
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first() ??
            Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'web']);

        $students = [];
        for ($i = 0; $i < $studentCount; $i++) {
            $students[] = User::factory()
                ->state([
                    'birthdate' => $this->faker->dateTimeBetween('-25 years', '-15 years')->format('Y-m-d'),
                ])
                ->afterCreating(fn (User $user) => $user->assignRole($studentRole))
                ->create();
        }

        // Crear relaciones
        foreach ($parents as $parent) {
            foreach ($students as $student) {
                $family[] = ParentStudent::factory()
                    ->forParent($parent)
                    ->forStudent($student)
                    ->state([
                        'relationship' => isset($parent->gender) && $parent->gender === 'MASCULINO' ?
                            RelationshipType::PADRE : RelationshipType::MADRE,
                    ])
                    ->create();
            }
        }

        return $family;
    }

    /**
     * Configure the factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ParentStudent $parentStudent) {
            // Validaciones adicionales si es necesario
        })->afterCreating(function (ParentStudent $parentStudent) {
            // Acciones después de persistir
        });
    }

    /**
     * Create without persisting to database.
     */
    public function makeParentStudent(array $attributes = []): array
    {
        return $this->state($attributes)->make()->toArray();
    }
}
