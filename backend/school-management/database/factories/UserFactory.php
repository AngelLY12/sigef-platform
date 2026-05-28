<?php

namespace Database\Factories;

use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */


    public function definition(): array
    {
        $gender = $this->faker->randomElement(UserGender::cases());
        $bloodTypes = UserBloodType::cases();
        return [
            'name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => $this->faker->numerify('55########'), // Formato MX: 55 + 8 dígitos
            'birthdate' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => $gender,
            'curp' => $this->generateCURP($gender),
            'address' => [
                'street' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'postal_code' => $this->faker->postcode(),
                'country' => 'México',
            ],
            'password' => Hash::make('password'), // Contraseña por defecto
            'stripe_customer_id' => 'cus_' . Str::random(14),
            'blood_type' => $this->faker->optional(0.7)->randomElement($bloodTypes), // 70% de probabilidad
            'registration_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'status' => UserStatus::ACTIVO,
            'email_verified_at' => $this->faker->boolean(80)
                ? now()->subDays(rand(1, 365))
                : null,
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }


    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::BAJA_TEMPORAL,
        ]);
    }

    /**
     * Indicate that the user is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::BAJA,
        ]);
    }

    /**
     * Indicate that the user is a student.
     * Útil para cuando necesites crear usuarios con role de estudiante
     */
    public function asStudent(): static
    {
        return $this->state(fn (array $attributes) => [
            'birthdate' => $this->faker->dateTimeBetween('-25 years', '-15 years')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the user is a parent.
     * Útil para cuando necesites crear usuarios con role de padre/madre
     */
    public function asParent(): static
    {
        return $this->state(fn (array $attributes) => [
            'birthdate' => $this->faker->dateTimeBetween('-50 years', '-30 years')->format('Y-m-d'),
        ]);
    }

    /**
     * Generate a realistic CURP for Mexican users.
     * Nota: Esta es una simulación básica, no genera CURPs válidos reales
     */
    private function generateCURP(UserGender $gender): string
    {
        $firstName = substr($this->faker->firstName(), 0, 1);
        $lastName = substr($this->faker->lastName(), 0, 2);
        $secondLastName = substr($this->faker->lastName(), 0, 1);
        $birthYear = substr($this->faker->year(), 2, 2);
        $birthMonth = str_pad($this->faker->month(), 2, '0', STR_PAD_LEFT);
        $birthDay = str_pad($this->faker->dayOfMonth(), 2, '0', STR_PAD_LEFT);
        $stateCode = $this->faker->randomElement(['AS', 'BC', 'BS', 'CC', 'CS', 'CH', 'CL', 'CM', 'DF', 'DG', 'GT', 'GR', 'HG', 'JC', 'MC', 'MN', 'MS', 'NT', 'NL', 'OC', 'PL', 'QO', 'QR', 'SP', 'SL', 'SR', 'TC', 'TS', 'TL', 'VZ', 'YN', 'ZS']);
        $genderCode = $gender === UserGender::HOMBRE ? 'H' : 'M';

        return strtoupper(
            $lastName .
            $secondLastName .
            $firstName .
            $birthYear .
            $birthMonth .
            $birthDay .
            $genderCode .
            $stateCode .
            'ABC' . // Tres consonantes aleatorias
            $this->faker->randomDigit() // Dígito verificador
        );
    }
}
