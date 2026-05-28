<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Infraestructure\Repositories\Command\Misc\EloquentDBRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentDBRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private EloquentDBRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentDBRepository();
    }

    #[Test]
    public function checkDBStatus_returns_true_when_database_is_accessible(): void
    {
        // Act
        $result = $this->repository->checkDBStatus();

        // Assert
        $this->assertTrue($result, 'Database should be accessible in test environment');
    }

    #[Test]
    public function checkDBStatus_connects_to_database_successfully(): void
    {
        // Arrange & Act
        $result = $this->repository->checkDBStatus();

        // Assert - No exception should be thrown
        $this->assertTrue($result);

        // Verify we can actually query
        $tables = DB::select('SHOW TABLES');
        $this->assertIsArray($tables);
    }

    #[Test]
    public function checkDBStatus_returns_true_when_tables_exist(): void
    {
        // Act
        $result = $this->repository->checkDBStatus();

        // Assert
        $this->assertTrue($result);

        // There should be at least some tables (users, migrations, etc.)
        $tables = DB::select('SHOW TABLES');
        $this->assertGreaterThan(0, count($tables), 'Should have at least one table');
    }

    #[Test]
    public function checkDBStatus_can_be_called_multiple_times(): void
    {
        // Act - Call multiple times
        $result1 = $this->repository->checkDBStatus();
        $result2 = $this->repository->checkDBStatus();
        $result3 = $this->repository->checkDBStatus();

        // Assert - All should be true
        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue($result3);
    }

}
