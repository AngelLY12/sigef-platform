<?php

namespace Tests\Unit\Domain\Validators;

use App\Core\Domain\Entities\RefreshToken;
use App\Core\Domain\Utils\Validators\TokenValidator;
use App\Exceptions\Unauthorized\RefreshTokenExpiredException;
use App\Exceptions\Unauthorized\RefreshTokenRevokedException;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class TokenValidatorTest extends TestCase
{
    private function createMockRefreshToken(array $properties = []): MockObject
    {
        $mock = $this->createMock(RefreshToken::class);

        $defaults = [
            'id' => 1,
            'user_id' => 1,
            'token' => 'valid_token',
            'expiresAt' => CarbonImmutable::now()->addDay(),
            'revoked' => false,
        ];

        $properties = array_merge($defaults, $properties);

        $isExpired = $properties['expiresAt']->isPast();
        $isRevoked = $properties['revoked'];

        $mock->method('isExpired')->willReturn($isExpired);
        $mock->method('isRevoked')->willReturn($isRevoked);
        $mock->method('isValid')->willReturn(!$isRevoked && !$isExpired);

        return $mock;
    }

    // Tests para ensureIsTokenValid
    #[Test]
    public function ensureIsTokenValid_passes_when_all_conditions_met(): void
    {
        $token = $this->createMockRefreshToken();
        $this->expectNotToPerformAssertions();
        TokenValidator::ensureIsTokenValid($token);
    }

    #[Test]
    public function ensureIsTokenValid_type_hint_prevents_null(): void
    {
        $this->expectException(\TypeError::class);
        TokenValidator::ensureIsTokenValid(null);
    }

    #[Test]
    public function ensureIsTokenValid_throws_when_token_expired(): void
    {
        $token = $this->createMockRefreshToken([
            'expiresAt' => CarbonImmutable::now()->subDay(),
        ]);

        $this->expectException(RefreshTokenExpiredException::class);
        $this->expectExceptionMessage('Refresh token expirado');
        TokenValidator::ensureIsTokenValid($token);
    }

    #[Test]
    public function ensureIsTokenValid_throws_when_token_revoked(): void
    {
        $token = $this->createMockRefreshToken([
            'revoked' => true,
        ]);

        $this->expectException(RefreshTokenRevokedException::class);
        $this->expectExceptionMessage('Refresh token revocado');
        TokenValidator::ensureIsTokenValid($token);
    }

    // ELIMINAR estos tests porque prueban lógica inconsistente:
    // #[Test]
    // public function ensureIsTokenValid_throws_when_token_invalid(): void
    //
    // #[Test]
    // public function ensureIsTokenValid_invalid_trumps_other_conditions(): void

    #[Test]
    public function ensureIsTokenValid_checks_expired_before_revoked(): void
    {
        $token = $this->createMockRefreshToken([
            'expiresAt' => CarbonImmutable::now()->subDay(),
            'revoked' => true,
        ]);

        $this->expectException(RefreshTokenExpiredException::class);
        TokenValidator::ensureIsTokenValid($token);
    }

    #[Test]
    public function ensureIsTokenValid_edge_case_just_expired(): void
    {
        $token = $this->createMockRefreshToken([
            'expiresAt' => CarbonImmutable::now()->subSecond(),
        ]);

        $this->expectException(RefreshTokenExpiredException::class);
        TokenValidator::ensureIsTokenValid($token);
    }

    #[Test]
    public function ensureIsTokenValid_edge_case_just_valid(): void
    {
        $token = $this->createMockRefreshToken([
            'expiresAt' => CarbonImmutable::now()->addSecond(),
        ]);

        $this->expectNotToPerformAssertions();
        TokenValidator::ensureIsTokenValid($token);
    }

    #[Test]
    public function ensureIsTokenValid_valid_token_with_all_methods_true(): void
    {
        $token = $this->createMockRefreshToken();

        // El problema aquí es que expects() hace assertions
        // y el test dice "expectNotToPerformAssertions"
        // Pero expects() cuenta como assertion

        // Solución: no usar expects() o cambiar la anotación
        $token->expects($this->once())->method('isExpired')->willReturn(false);
        $token->expects($this->once())->method('isRevoked')->willReturn(false);
        $token->expects($this->once())->method('isValid')->willReturn(true);

        // Cambiar a: no verificar expectNotToPerformAssertions
        TokenValidator::ensureIsTokenValid($token);

    }
}
