<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;
use App\Models\RefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Core\Infraestructure\Repositories\Command\Auth\EloquentRefreshTokenRepository;
use App\Core\Domain\Entities\RefreshToken as DomainRefreshToken;
use App\Core\Domain\Enum\User\UserRoles;
use App\Models\RefreshToken as ModelsRefreshToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;

class EloquentRefreshTokenRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private EloquentRefreshTokenRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentRefreshTokenRepository();

        // Limpiar tokens antes de cada test
        DB::table('refresh_tokens')->delete();
    }

    #[Test]
    public function findByToken_returns_domain_refresh_token_when_found(): void
    {
        // Arrange
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()
            ->forUser($user)
            ->create(['token' => hash('sha256', 'test-token-123')]);

        // Act
        $result = $this->repository->findByToken('test-token-123');

        // Assert
        $this->assertInstanceOf(DomainRefreshToken::class, $result);
        $this->assertEquals($refreshToken->id, $result->id);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals(hash('sha256', 'test-token-123'), $result->token);
    }

    #[Test]
    public function findByToken_throws_model_not_found_exception_when_token_not_found(): void
    {
        // Arrange
        $nonExistentToken = 'non-existent-token';

        // Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('El token no fue encontrado');

        // Act
        $this->repository->findByToken($nonExistentToken);
    }

    #[Test]
    public function revokeRefreshToken_returns_true_when_token_revoked_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()
            ->forUser($user)
            ->create([
                'token' => hash('sha256', 'test-token'),
                'revoked' => false
            ]);

        // Act
        $result = $this->repository->revokeRefreshToken('test-token');

        // Assert
        $this->assertTrue($result);

        // Verificar en BD
        $this->assertDatabaseHas('refresh_tokens', [
            'id' => $refreshToken->id,
            'revoked' => true
        ]);
    }

    #[Test]
    public function revokeRefreshToken_returns_false_when_token_already_revoked(): void
    {
        // Arrange
        $user = User::factory()->create();
        RefreshToken::factory()
            ->forUser($user)
            ->create([
                'token' => hash('sha256', 'already-revoked'),
                'revoked' => true
            ]);

        // Act
        $result = $this->repository->revokeRefreshToken('already-revoked');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function revokeRefreshToken_returns_false_when_token_not_found(): void
    {
        // Act
        $result = $this->repository->revokeRefreshToken('non-existent');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function update_updates_token_and_returns_domain_entity(): void
    {
        // Arrange
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()
            ->forUser($user)
            ->create(['revoked' => false]);

        $newExpiresAt = Carbon::now()->addDays(30);
        $fields = [
            'expires_at' => $newExpiresAt,
            'revoked' => true,
        ];

        // Act
        $result = $this->repository->update($refreshToken->id, $fields);

        // Assert
        $this->assertInstanceOf(DomainRefreshToken::class, $result);
        $this->assertEquals($refreshToken->id, $result->id);
        $this->assertTrue($result->isRevoked());

        // Verificar en BD
        $this->assertDatabaseHas('refresh_tokens', [
            'id' => $refreshToken->id,
            'revoked' => true
        ]);
    }

    #[Test]
    public function update_throws_exception_when_token_not_found(): void
    {
        // Arrange
        $nonExistentId = 999999;
        $fields = ['revoked' => true];

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->repository->update($nonExistentId, $fields);
    }

    #[Test]
    public function delete_removes_token_from_database(): void
    {
        // Arrange
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()
            ->forUser($user)
            ->create();

        // Act
        $this->repository->delete($refreshToken->id);

        // Assert
        $this->assertDatabaseMissing('refresh_tokens', ['id' => $refreshToken->id]);
    }

    #[Test]
    public function delete_throws_exception_when_token_not_found(): void
    {
        // Arrange
        $nonExistentId = 999999;

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->repository->delete($nonExistentId);
    }

    #[Test]
    public function deletionInvalidTokens_deletes_invalid_tokens(): void
    {
        // Arrange
        $now = Carbon::now();

        // Token expirado no revocado
        RefreshToken::factory()
            ->create([
                'expires_at' => $now->copy()->subDays(1),
                'revoked' => false
            ]);

        // Token expirado ya revocado
        RefreshToken::factory()
            ->create([
                'expires_at' => $now->copy()->subDays(2),
                'revoked' => true
            ]);

        // Token no expirado revocado
        RefreshToken::factory()
            ->create([
                'expires_at' => $now->copy()->addDays(7),
                'revoked' => true
            ]);

        // Token válido (no expirado, no revocado) - NO debe ser eliminado
        $validToken = RefreshToken::factory()
            ->create([
                'expires_at' => $now->copy()->addDays(30),
                'revoked' => false
            ]);

        // Act
        $deletedCount = $this->repository->deletionInvalidTokens();

        // Assert
        $this->assertEquals(3, $deletedCount);

        // Solo el token válido debe quedar
        $this->assertDatabaseHas('refresh_tokens', ['id' => $validToken->id]);
        $this->assertDatabaseCount('refresh_tokens', 1);
    }

    #[Test]
    public function deletionInvalidTokens_returns_zero_when_no_invalid_tokens(): void
    {
        // Arrange - solo tokens válidos
        RefreshToken::factory()
            ->create(['expires_at' => Carbon::now()->addDays(7), 'revoked' => false]);
        RefreshToken::factory()
            ->create(['expires_at' => Carbon::now()->addDays(30), 'revoked' => false]);

        // Act
        $deletedCount = $this->repository->deletionInvalidTokens();

        // Assert
        $this->assertEquals(0, $deletedCount);
        $this->assertDatabaseCount('refresh_tokens', 2);
    }

    #[Test]
    public function deletionInvalidTokens_handles_empty_table(): void
    {
        // Act
        $deletedCount = $this->repository->deletionInvalidTokens();

        // Assert
        $this->assertEquals(0, $deletedCount);
    }

    #[Test]
    public function revokeRefreshToken_does_not_affect_other_tokens(): void
    {
        // Arrange
        $user = User::factory()->create();

        $token1 = RefreshToken::factory()
            ->forUser($user)
            ->create([
                'token' => hash('sha256', 'token-1'),
                'revoked' => false
            ]);

        $token2 = RefreshToken::factory()
            ->forUser($user)
            ->create([
                'token' => hash('sha256', 'token-2'),
                'revoked' => false
            ]);

        $token3 = RefreshToken::factory()
            ->create([
                'token' => hash('sha256', 'token-3'),
                'revoked' => false
            ]);

        // Act
        $this->repository->revokeRefreshToken('token-2');

        // Assert
        $this->assertDatabaseHas('refresh_tokens', [
            'id' => $token1->id,
            'revoked' => false
        ]);

        $this->assertDatabaseHas('refresh_tokens', [
            'id' => $token2->id,
            'revoked' => true
        ]);

        $this->assertDatabaseHas('refresh_tokens', [
            'id' => $token3->id,
            'revoked' => false
        ]);
    }
}
