<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\NewAccessToken;
use Tests\TestCase;
use App\Core\Infraestructure\Repositories\Command\Auth\EloquentAccessTokenRepository;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class EloquentAccessTokenRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private EloquentAccessTokenRepository $repository;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentAccessTokenRepository();
        $this->testUser = User::factory()->create();
        DB::table('personal_access_tokens')->delete();

    }

    #[Test]
    public function revokeToken_deletes_token_and_returns_true_when_found(): void
    {
        // Arrange
        $token = $this->createAccessToken($this->testUser, 'test-token');

        // Act
        $result = $this->repository->revokeToken($token->accessToken->id);

        // Assert
        $this->assertTrue($result);
        $this->assertNull(PersonalAccessToken::find($token->accessToken->id));
    }

    #[Test]
    public function revokeToken_returns_false_when_token_not_found(): void
    {
        // Arrange
        $nonExistentId = '999999';

        // Act
        $result = $this->repository->revokeToken($nonExistentId);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function deletionInvalidTokens_deletes_only_expired_tokens(): void
    {
        // Arrange
        // Crear tokens válidos (no expirados)
        $validToken1 = $this->createAccessToken($this->testUser, 'valid-token-1', Carbon::now()->addDays(7));
        $validToken2 = $this->createAccessToken(User::factory()->create(), 'valid-token-2', Carbon::now()->addDays(30));

        // Crear tokens expirados
        $expiredToken1 = $this->createAccessToken(User::factory()->create(), 'expired-token-1', Carbon::now()->subDays(1));
        $expiredToken2 = $this->createAccessToken(User::factory()->create(), 'expired-token-2', Carbon::now()->subDays(7));

        // Token sin fecha de expiración (no debe ser eliminado)
        $tokenWithoutExpiration = $this->createAccessToken(User::factory()->create(), 'no-expiration', null);

        // Act
        $deletedCount = $this->repository->deletionInvalidTokens();

        // Assert
        $this->assertEquals(2, $deletedCount);

        // Verificar que solo los expirados fueron eliminados
        $this->assertNull(PersonalAccessToken::find($expiredToken1->accessToken->id));
        $this->assertNull(PersonalAccessToken::find($expiredToken2->accessToken->id));

        // Verificar que los demás tokens siguen existiendo
        $this->assertNotNull(PersonalAccessToken::find($validToken1->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($validToken2->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($tokenWithoutExpiration->accessToken->id));
    }

    #[Test]
    public function deletionInvalidTokens_returns_zero_when_no_expired_tokens(): void
    {
        // Arrange
        // Crear solo tokens válidos
        $this->createAccessToken($this->testUser, 'valid-token', Carbon::now()->addDays(1));

        // Act
        $deletedCount = $this->repository->deletionInvalidTokens();

        // Assert
        $this->assertEquals(0, $deletedCount);
    }

    #[Test]
    public function deletionInvalidTokens_handles_empty_table(): void
    {
        // Arrange - Vaciar la tabla
        DB::table('personal_access_tokens')->delete();

        // Act
        $deletedCount = $this->repository->deletionInvalidTokens();

        // Assert
        $this->assertEquals(0, $deletedCount);
    }

    #[Test]
    public function deletionInvalidTokens_with_mixed_expiration_status(): void
    {
        // Arrange
        $now = Carbon::now();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user4 = User::factory()->create();

        // Token expirado hace mucho
        $oldExpired = $this->createAccessToken($this->testUser, 'old-expired', $now->copy()->subMonths(6));

        // Token que expira justo ahora (debería ser eliminado)
        $justExpired = $this->createAccessToken($user2, 'just-expired', $now->copy()->subSecond());

        // Token que expira en el futuro cercano (no debe ser eliminado)
        $futureExpiration = $this->createAccessToken($user3, 'future', $now->copy()->addSecond());

        // Token sin fecha de expiración
        $noExpiration = $this->createAccessToken($user4, 'no-exp', null);

        // Act
        $deletedCount = $this->repository->deletionInvalidTokens();

        // Assert
        $this->assertEquals(2, $deletedCount); // oldExpired y justExpired
        $this->assertNull(PersonalAccessToken::find($oldExpired->accessToken->id));
        $this->assertNull(PersonalAccessToken::find($justExpired->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($futureExpiration->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($noExpiration->accessToken->id));
    }

    #[Test]
    public function deletionInvalidTokens_only_deletes_tokens_with_expires_at_field(): void
    {
        // Arrange
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Token con fecha de expiración expirada
        $expiredWithDate = $this->createAccessToken($this->testUser, 'expired-with-date', Carbon::now()->subDays(1));

        // Token sin fecha de expiración
        $noExpiration = $this->createAccessToken($user2, 'no-date', null);

        // Token con fecha futura
        $future = $this->createAccessToken($user3, 'future', Carbon::now()->addDays(1));

        // Act
        $deletedCount = $this->repository->deletionInvalidTokens();

        // Assert
        $this->assertEquals(1, $deletedCount);
        $this->assertNull(PersonalAccessToken::find($expiredWithDate->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($noExpiration->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($future->accessToken->id));
    }

    /**
     * Helper para crear access tokens de prueba
     */
    private function createAccessToken(User $user, string $tokenName, ?Carbon $expiresAt = null): NewAccessToken
    {
        return $user->createToken($tokenName, ['*'], $expiresAt);
    }
}
