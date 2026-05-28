<?php

namespace Tests\Unit\Domain\Repositories\Command;

use Tests\Stubs\Repositories\Command\DBRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Command\Misc\DBRepInterface;
use PHPUnit\Framework\Attributes\Test;

class DBRepInterfaceTest extends BaseRepositoryTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = DBRepInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos un stub para probar el contrato
        $this->repository = new DBRepStub();
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

        $methods = ['checkDBStatus'];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_check_db_status(): void
    {
        $result = $this->repository->checkDBStatus();

        $this->assertIsBool($result);
    }

    #[Test]
    public function check_db_status_returns_true_when_db_is_healthy(): void
    {
        $stub = new DBRepStub();
        $stub->setDBStatus(true);

        $result = $stub->checkDBStatus();

        $this->assertTrue($result);
    }

    #[Test]
    public function check_db_status_returns_false_when_db_is_unhealthy(): void
    {
        $stub = new DBRepStub();
        $stub->setDBStatus(false);

        $result = $stub->checkDBStatus();

        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_database_connection_errors(): void
    {
        $stub = new DBRepStub();
        $stub->shouldThrowConnectionError(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection error');

        $stub->checkDBStatus();
    }

    #[Test]
    public function db_status_check_verifies_tables_exist(): void
    {
        $stub = new DBRepStub();

        // Simular que hay tablas
        $stub->setTablesCount(5);
        $result = $stub->checkDBStatus();
        $this->assertTrue($result);

        // Simular que no hay tablas
        $stub->setTablesCount(0);
        $result = $stub->checkDBStatus();
        $this->assertFalse($result);
    }
}
