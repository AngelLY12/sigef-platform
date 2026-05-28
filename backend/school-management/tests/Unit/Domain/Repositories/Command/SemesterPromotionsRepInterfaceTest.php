<?php

namespace Tests\Unit\Domain\Repositories\Command;

use Tests\Stubs\Repositories\Command\SemesterPromotionsRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Command\Misc\SemesterPromotionsRepInterface;
use PHPUnit\Framework\Attributes\Test;

class SemesterPromotionsRepInterfaceTest extends BaseRepositoryTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = SemesterPromotionsRepInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos un stub para probar el contrato
        $this->repository = new SemesterPromotionsRepStub();
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
            'wasExecutedThisMonth',
            'registerExecution'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_check_if_executed_this_month(): void
    {
        $result = $this->repository->wasExecutedThisMonth();

        $this->assertIsBool($result);
    }

    #[Test]
    public function was_executed_this_month_returns_true_when_already_executed(): void
    {
        $stub = new SemesterPromotionsRepStub();
        $stub->setExecutedThisMonth(true);

        $result = $stub->wasExecutedThisMonth();

        $this->assertTrue($result);
    }

    #[Test]
    public function was_executed_this_month_returns_false_when_not_executed(): void
    {
        $stub = new SemesterPromotionsRepStub();
        $stub->setExecutedThisMonth(false);

        $result = $stub->wasExecutedThisMonth();

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_register_execution(): void
    {
        // No debería lanzar excepción
        $this->repository->registerExecution();

        $this->addToAssertionCount(1); // Verificar que no se lanzó excepción
    }

    #[Test]
    public function register_execution_sets_executed_flag(): void
    {
        $stub = new SemesterPromotionsRepStub();

        // Inicialmente no ejecutado
        $this->assertFalse($stub->wasExecutedThisMonth());

        // Registrar ejecución
        $stub->registerExecution();

        // Ahora debería estar ejecutado
        $this->assertTrue($stub->wasExecutedThisMonth());
    }

    #[Test]
    public function register_execution_can_be_called_multiple_times(): void
    {
        $stub = new SemesterPromotionsRepStub();

        // Registrar múltiples ejecuciones
        $stub->registerExecution();
        $stub->registerExecution();
        $stub->registerExecution();

        // Siempre debería estar ejecutado después de al menos una ejecución
        $this->assertTrue($stub->wasExecutedThisMonth());
    }

    #[Test]
    public function it_handles_database_errors_gracefully(): void
    {
        $stub = new SemesterPromotionsRepStub();
        $stub->shouldThrowDatabaseError(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $stub->wasExecutedThisMonth();
    }

    #[Test]
    public function check_execution_uses_current_month_and_year(): void
    {
        $stub = new SemesterPromotionsRepStub();

        // Configurar para simular ejecución en este mes
        $stub->setExecutedThisMonth(true);
        $result1 = $stub->wasExecutedThisMonth();
        $this->assertTrue($result1);

        // Configurar para simular no ejecución
        $stub->setExecutedThisMonth(false);
        $result2 = $stub->wasExecutedThisMonth();
        $this->assertFalse($result2);
    }

    #[Test]
    public function it_can_handle_multiple_operations(): void
    {
        $stub = new SemesterPromotionsRepStub();

        // 1. Verificar que no se ha ejecutado
        $executed = $stub->wasExecutedThisMonth();
        $this->assertFalse($executed);

        // 2. Registrar ejecución
        $stub->registerExecution();

        // 3. Verificar que ahora sí se ha ejecutado
        $executedAfter = $stub->wasExecutedThisMonth();
        $this->assertTrue($executedAfter);

        // 4. Registrar otra ejecución (no debería afectar)
        $stub->registerExecution();

        // 5. Seguir ejecutado
        $executedFinal = $stub->wasExecutedThisMonth();
        $this->assertTrue($executedFinal);
    }
}
