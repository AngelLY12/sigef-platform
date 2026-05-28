<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Infraestructure\Repositories\Command\Misc\EloquentSemesterPromotionsRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentSemesterPromotionsRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private EloquentSemesterPromotionsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentSemesterPromotionsRepository();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function wasExecutedThisMonth_returns_false_when_no_executions(): void
    {
        $this->assertFalse($this->repository->wasExecutedThisMonth());
    }

    #[Test]
    public function wasExecutedThisMonth_returns_false_for_last_month(): void
    {
        $lastMonth = Carbon::now()->subMonths(2)
            ->day(15)
            ->setTime(12, 0, 0);
        DB::table('semester_promotions')->insert([
            ['executed_at' => $lastMonth]
        ]);

        $this->assertFalse($this->repository->wasExecutedThisMonth());
    }

    #[Test]
    public function wasExecutedThisMonth_returns_true_for_this_month(): void
    {
        DB::table('semester_promotions')->insert([
            ['executed_at' => Carbon::now()]
        ]);

        $this->assertTrue($this->repository->wasExecutedThisMonth());
    }

    #[Test]
    public function wasExecutedThisMonth_returns_true_for_beginning_of_month(): void
    {
        DB::table('semester_promotions')->insert([
            ['executed_at' => Carbon::now()->startOfMonth()]
        ]);

        $this->assertTrue($this->repository->wasExecutedThisMonth());
    }

    #[Test]
    public function wasExecutedThisMonth_returns_true_for_end_of_month(): void
    {
        DB::table('semester_promotions')->insert([
            ['executed_at' => Carbon::now()->endOfMonth()]
        ]);

        $this->assertTrue($this->repository->wasExecutedThisMonth());
    }

    #[Test]
    public function wasExecutedThisMonth_returns_false_for_last_year(): void
    {
        $lastYear = Carbon::now()->subYear();
        DB::table('semester_promotions')->insert([
            ['executed_at' => $lastYear]
        ]);

        $this->assertFalse($this->repository->wasExecutedThisMonth());
    }

    #[Test]
    public function registerExecution_inserts_current_time(): void
    {
        $this->repository->registerExecution();

        $record = DB::table('semester_promotions')->first();
        $this->assertNotNull($record);

        $executedAt = Carbon::parse($record->executed_at);
        $this->assertTrue($executedAt->isToday());
    }

    #[Test]
    public function registerExecution_multiple_times(): void
    {
        $initialCount = DB::table('semester_promotions')->count();

        $this->repository->registerExecution();
        $this->repository->registerExecution();

        $finalCount = DB::table('semester_promotions')->count();
        $this->assertEquals($initialCount + 2, $finalCount);
    }

    #[Test]
    public function integration_test(): void
    {
        // Inicialmente false
        $this->assertFalse($this->repository->wasExecutedThisMonth());

        // Registrar ejecución
        $this->repository->registerExecution();

        // Ahora debe ser true
        $this->assertTrue($this->repository->wasExecutedThisMonth());

        // Verificar el registro
        $record = DB::table('semester_promotions')->first();
        $this->assertNotNull($record);
        $this->assertEquals(Carbon::now()->year, Carbon::parse($record->executed_at)->year);
        $this->assertEquals(Carbon::now()->month, Carbon::parse($record->executed_at)->month);
    }
}
