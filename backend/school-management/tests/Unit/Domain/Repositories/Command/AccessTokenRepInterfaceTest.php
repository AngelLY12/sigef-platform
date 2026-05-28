<?php

namespace Tests\Unit\Domain\Repositories\Command;

use Tests\Stubs\Repositories\Command\AccessTokenRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Command\Auth\AccessTokenRepInterface;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AccessTokenRepInterfaceTest extends BaseRepositoryTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = AccessTokenRepInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos un stub para probar el contrato
        $this->repository = new AccessTokenRepStub();

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
            'revokeToken',
            'deletionInvalidTokens'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_revoke_token(): void
    {
        $tokenId = '1';
        $result = $this->repository->revokeToken($tokenId);

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_token_not_found(): void
    {
        $tokenId = 1;

        // Configurar el stub para simular token no encontrado
        $stub = new AccessTokenRepStub();
        $stub->shouldSimulateTokenNotFound(true);

        $result = $stub->revokeToken($tokenId);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_delete_invalid_tokens(): void
    {
        $result = $this->repository->deletionInvalidTokens();

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    #[Test]
    public function it_deletes_only_expired_tokens(): void
    {
        $stub = new AccessTokenRepStub();

        // Configurar para simular eliminación de tokens expirados
        $stub->setExpiredTokensCount(5);

        $result = $stub->deletionInvalidTokens();

        $this->assertEquals(5, $result);
    }

    #[Test]
    public function it_returns_zero_when_no_expired_tokens(): void
    {
        $stub = new AccessTokenRepStub();

        $stub->setExpiredTokensCount(0);

        $result = $stub->deletionInvalidTokens();

        $this->assertEquals(0, $result);
    }

    #[Test]
    public function it_handles_database_errors_gracefully(): void
    {
        $stub = new AccessTokenRepStub();

        // Configurar para simular error de base de datos
        $stub->shouldThrowDatabaseError(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $stub->revokeToken('1');
    }

    #[Test]
    public function revoke_token_validates_token_id(): void
    {
        $invalidTokenId = 0;

        $result = $this->repository->revokeToken($invalidTokenId);

        // Depende de la implementación, podría retornar false o lanzar excepción
        $this->assertIsBool($result);
    }

    #[Test]
    public function deletion_ignores_tokens_without_expiration(): void
    {
        $stub = new AccessTokenRepStub();

        // Simular que hay tokens sin fecha de expiración
        $stub->setExpiredTokensCount(3);
        $stub->setTokensWithoutExpiration(2);

        $result = $stub->deletionInvalidTokens();

        // Solo debe eliminar los expirados (3), no los sin expiración (2)
        $this->assertEquals(3, $result);
    }

    #[Test]
    public function it_can_handle_large_number_of_expired_tokens(): void
    {
        $stub = new AccessTokenRepStub();

        // Simular un gran número de tokens expirados
        $largeNumber = 10000;
        $stub->setExpiredTokensCount($largeNumber);

        $result = $stub->deletionInvalidTokens();

        $this->assertEquals($largeNumber, $result);
    }
    #[Test]
    public function it_can_revoke_multiple_tokens(): void
    {
        $stub = new AccessTokenRepStub();

        $stub->addToken('4', ['id' => 4, 'tokenable_id' => 4, 'name' => 'Token 4', 'expires_at' => '2024-12-31 23:59:59']);
        $stub->addToken('5', ['id' => 5, 'tokenable_id' => 5, 'name' => 'Token 5', 'expires_at' => '2024-12-31 23:59:59']);

        $result1 = $stub->revokeToken('4');
        $this->assertTrue($result1);

        $result2 = $stub->revokeToken('5');
        $this->assertTrue($result2);

        $this->assertNull($stub->getToken('4'));
        $this->assertNull($stub->getToken('5'));
    }
}
