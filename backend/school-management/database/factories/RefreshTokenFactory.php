<?php

namespace Database\Factories;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RefreshToken>
 */
class RefreshTokenFactory extends Factory
{
    protected $model = RefreshToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Fecha de expiración aleatoria entre 1 día y 30 días
        $expiresAt = $this->faker->dateTimeBetween('+1 day', '+30 days');

        return [
            'user_id' => User::factory(),
            'token' => Str::random(64), // Token de 64 caracteres
            'expires_at' => $expiresAt,
            'revoked' => false,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }

    /**
     * Indicate that the refresh token belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the refresh token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'revoked' => $this->faker->boolean(20), // 20% revocados aunque ya expirados
        ]);
    }

    /**
     * Indicate that the refresh token is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => true,
            'expires_at' => $this->faker->dateTimeBetween('-10 days', '+20 days'), // Puede estar vigente o no
        ]);
    }

    /**
     * Indicate that the refresh token is active (not revoked and not expired).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => false,
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+30 days'),
        ]);
    }

    /**
     * Indicate that the refresh token is about to expire.
     */
    public function aboutToExpire(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => false,
            'expires_at' => $this->faker->dateTimeBetween('+1 minute', '+5 minutes'),
        ]);
    }

    /**
     * Indicate that the refresh token is for a long session.
     */
    public function longLived(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => false,
            'expires_at' => $this->faker->dateTimeBetween('+30 days', '+90 days'), // 1-3 meses
        ]);
    }

    /**
     * Indicate that the refresh token is for a short session.
     */
    public function shortLived(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => false,
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+24 hours'),
        ]);
    }

    /**
     * Indicate that the refresh token has a specific token value.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token' => $token,
        ]);
    }

    /**
     * Indicate that the refresh token expires at a specific date.
     */
    public function expiresAt(\DateTimeInterface $expiresAt): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Indicate that the refresh token was created recently.
     */
    public function recentlyCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+7 days'),
            'revoked' => false,
        ]);
    }

    /**
     * Indicate that the refresh token is for a mobile app (tokens más largos).
     */
    public function forMobileApp(): static
    {
        return $this->state(fn (array $attributes) => [
            'token' => Str::random(128), // Token más largo para apps móviles
            'expires_at' => $this->faker->dateTimeBetween('+30 days', '+180 days'), // Sesiones más largas
        ]);
    }

    /**
     * Indicate that the refresh token is for a web session.
     */
    public function forWebSession(): static
    {
        return $this->state(fn (array $attributes) => [
            'token' => Str::random(64),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+7 days'), // Sesiones más cortas
        ]);
    }

    /**
     * Create multiple refresh tokens for the same user (simulating multiple devices).
     */
    public function multipleForUser(User $user, int $count = 3): array
    {
        $tokens = [];

        for ($i = 0; $i < $count; $i++) {
            $tokens[] = RefreshToken::factory()
                ->forUser($user)
                ->state([
                    'token' => Str::random(64) . $i, // Token único
                    'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
                    'revoked' => $this->faker->boolean(10), // 10% revocados
                ])
                ->create();
        }

        return $tokens;
    }

    /**
     * Indicate that the refresh token should use JWT format.
     * Nota: Esto es solo una simulación, no genera JWT válidos reales.
     */
    public function jwtFormat(): static
    {
        return $this->state(fn (array $attributes) => [
            'token' => implode('.', [
                base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])),
                base64_encode(json_encode(['sub' => $attributes['user_id'], 'exp' => $attributes['expires_at']->getTimestamp()])),
                Str::random(43) // Firma simulada
            ]),
        ]);
    }
}
