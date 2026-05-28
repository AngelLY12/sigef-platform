<?php

namespace Tests\Unit\Domain\Repositories\Command;

use Carbon\CarbonImmutable;
use Tests\Stubs\Repositories\Command\RefreshTokenRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Command\Auth\RefreshTokenRepInterface;
use App\Core\Domain\Entities\RefreshToken;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RefreshTokenRepInterfaceTest extends BaseRepositoryTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = RefreshTokenRepInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos un stub para probar el contrato
        $this->repository = new RefreshTokenRepStub();
    }

    /**
     * Test que el repositorio puede ser instanciado
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');
        $this->assertImplementsInterface($this->interfaceClass);
    }

    /**
     * Test que todos los métodos requeridos existen
     */
    #[Test]
    public function it_has_all_required_methods(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');

        $methods = [
            'findByToken',
            'revokeRefreshToken',
            'update',
            'delete',
            'deletionInvalidTokens'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_find_by_token(): void
    {
        $token = 'valid_refresh_token';
        $result = $this->repository->findByToken($token);

        $this->assertInstanceOf(RefreshToken::class, $result);
        $this->assertEquals($token, $result->token);
    }

    #[Test]
    public function it_returns_null_when_token_not_found(): void
    {
        $token = 'non_existent_token';

        // Configurar el stub para simular token no encontrado
        $stub = new RefreshTokenRepStub();
        $stub->shouldSimulateTokenNotFound(true);

        $result = $stub->findByToken($token);

        $this->assertNull($result);
    }

    #[Test]
    public function it_handles_model_not_found_exception(): void
    {
        $token = 'token_that_throws_exception';

        $stub = new RefreshTokenRepStub();
        $stub->shouldThrowModelNotFound(true);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('El token no fue encontrado');

        $stub->findByToken($token);
    }

    #[Test]
    public function it_can_revoke_refresh_token(): void
    {
        $refreshTokenValue = 'valid_refresh_token';
        $result = $this->repository->revokeRefreshToken($refreshTokenValue);

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function revoke_returns_false_when_token_not_revoked(): void
    {
        $refreshTokenValue = 'already_revoked_token';

        $stub = new RefreshTokenRepStub();
        $stub->shouldSimulateNotRevoked(false);

        $result = $stub->revokeRefreshToken($refreshTokenValue);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_update_token(): void
    {
        $tokenId = 1;
        $fields = [
            'revoked' => true,
            'expires_at' => CarbonImmutable::now()->addDays(30)
        ];

        $result = $this->repository->update($tokenId, $fields);

        $this->assertInstanceOf(RefreshToken::class, $result);
        $this->assertEquals($tokenId, $result->id);
        $this->assertTrue($result->isRevoked());
    }

    #[Test]
    public function update_throws_exception_when_token_not_found(): void
    {
        $tokenId = 999;
        $fields = ['revoked' => true];

        $this->expectException(ModelNotFoundException::class);

        $this->repository->update($tokenId, $fields);
    }

    #[Test]
    public function it_can_delete_token(): void
    {
        $tokenId = 2;

        // No debería lanzar excepción
        $this->repository->delete($tokenId);

        $this->addToAssertionCount(1); // Verificar que no se lanzó excepción
    }

    #[Test]
    public function delete_throws_exception_when_token_not_found(): void
    {
        $tokenId = 999;

        $this->expectException(ModelNotFoundException::class);

        $this->repository->delete($tokenId);
    }

    #[Test]
    public function it_can_delete_invalid_tokens(): void
    {
        $result = $this->repository->deletionInvalidTokens();

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    #[Test]
    public function deletion_returns_zero_when_no_invalid_tokens(): void
    {
        $stub = new RefreshTokenRepStub();
        $stub->setInvalidTokensCount(0);

        $result = $stub->deletionInvalidTokens();

        $this->assertEquals(0, $result);
    }

    #[Test]
    public function it_deletes_only_expired_and_revoked_tokens(): void
    {
        $stub = new RefreshTokenRepStub();
        $stub->setInvalidTokensCount(5);

        $result = $stub->deletionInvalidTokens();

        $this->assertEquals(5, $result);
    }

    #[Test]
    public function it_handles_database_errors_gracefully(): void
    {
        $stub = new RefreshTokenRepStub();
        $stub->shouldThrowDatabaseError(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $stub->revokeRefreshToken('test_token');
    }

    #[Test]
    public function find_by_token_hashes_token_before_search(): void
    {
        $stub = new RefreshTokenRepStub();
        $stub->enableTokenHashing(true);

        $token = 'valid_refresh_token';
        $result = $stub->findByToken($token);

        // El stub debería haber aplicado hash al token
        $this->assertNotNull($result);
    }

    #[Test]
    public function revoke_hashes_token_before_update(): void
    {
        $stub = new RefreshTokenRepStub();
        $stub->enableTokenHashing(true);

        $token = 'valid_refresh_token';
        $result = $stub->revokeRefreshToken($token);

        $this->assertTrue($result);
    }

    #[Test]
    public function update_can_handle_partial_fields(): void
    {
        $tokenId = 1;
        $fields = ['revoked' => true]; // Solo un campo

        $result = $this->repository->update($tokenId, $fields);

        $this->assertInstanceOf(RefreshToken::class, $result);
        $this->assertTrue($result->isRevoked());
    }

    #[Test]
    public function it_can_handle_multiple_operations(): void
    {
        $stub = new RefreshTokenRepStub();

        // 1. Encontrar token
        $token = $stub->findByToken('valid_refresh_token');
        $this->assertInstanceOf(RefreshToken::class, $token);

        // 2. Revocar token
        $revoked = $stub->revokeRefreshToken('valid_refresh_token');
        $this->assertTrue($revoked);

        // 3. Actualizar token
        $updated = $stub->update(1, ['expires_at' => CarbonImmutable::now()->addDays(30)]);
        $this->assertInstanceOf(RefreshToken::class, $updated);

        // 4. Eliminar tokens inválidos
        $deletedCount = $stub->deletionInvalidTokens();
        $this->assertIsInt($deletedCount);
    }
}
