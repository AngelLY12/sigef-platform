<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Students\Dashboard;

use App\Core\Application\DTO\Response\Payment\PaymentsSummaryResponse;
use App\Core\Application\UseCases\Payments\Student\Dashboard\PaymentsMadeUseCase;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;
use App\Models\Payment as PaymentModel;

class PaymentsMadeUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private PaymentsMadeUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = app(PaymentsMadeUseCase::class);
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function it_returns_payments_summary_with_monthly_aggregation(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Crear pagos en diferentes meses
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::create(2024, 1, 15),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '200.00',
            'created_at' => Carbon::create(2024, 1, 20),
            'status' => PaymentStatus::OVERPAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '150.00',
            'created_at' => Carbon::create(2024, 2, 10),
            'status' => PaymentStatus::PAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '50.00',
            'created_at' => Carbon::create(2024, 3, 5),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Total: 100 + 200 + 150 + 50 = 500.00
        $this->assertEquals('500.00', $result->totalPayments);

        // Verificar estructura de pagos por mes
        $this->assertIsArray($result->paymentsByMonth);
        $this->assertCount(3, $result->paymentsByMonth);

        $this->assertArrayHasKey('2024-01', $result->paymentsByMonth);
        $this->assertArrayHasKey('2024-02', $result->paymentsByMonth);
        $this->assertArrayHasKey('2024-03', $result->paymentsByMonth);

        // Enero: 100 + 200 = 300.00
        $this->assertEquals('300.00', $result->paymentsByMonth['2024-01']);

        // Febrero: 150.00
        $this->assertEquals('150.00', $result->paymentsByMonth['2024-02']);

        // Marzo: 50.00
        $this->assertEquals('50.00', $result->paymentsByMonth['2024-03']);
    }

    #[Test]
    public function it_returns_only_current_year_payments_when_onlyThisYear_is_true(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        $currentYear = now()->year;
        $lastYear = $currentYear - 1;

        // Pagos del año actual
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::create($currentYear, 1, 15),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '200.00',
            'created_at' => Carbon::create($currentYear, 6, 20),
            'status' => PaymentStatus::PAID,
        ]);

        // Pagos del año anterior (no deben incluirse)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '500.00',
            'created_at' => Carbon::create($lastYear, 12, 31),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '300.00',
            'created_at' => Carbon::create($lastYear, 1, 1),
            'status' => PaymentStatus::OVERPAID,
        ]);

        $onlyThisYear = true;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Total solo del año actual: 100 + 200 = 300.00
        $this->assertEquals('300.00', $result->totalPayments);

        // Solo deben aparecer meses del año actual
        $this->assertCount(2, $result->paymentsByMonth);

        foreach ($result->paymentsByMonth as $month => $amount) {
            $this->assertStringStartsWith($currentYear . '-', $month);
        }
    }

    #[Test]
    public function it_returns_all_years_payments_when_onlyThisYear_is_false(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        $currentYear = now()->year;
        $lastYear = $currentYear - 1;

        // Pagos de diferentes años
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::create($lastYear, 12, 31),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '200.00',
            'created_at' => Carbon::create($currentYear, 1, 1),
            'status' => PaymentStatus::PAID,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Total de ambos años: 100 + 200 = 300.00
        $this->assertEquals('300.00', $result->totalPayments);

        // Deben aparecer meses de ambos años
        $this->assertCount(2, $result->paymentsByMonth);

        $this->assertArrayHasKey(sprintf('%d-12', $lastYear), $result->paymentsByMonth);
        $this->assertArrayHasKey(sprintf('%d-01', $currentYear), $result->paymentsByMonth);
    }

    #[Test]
    public function it_excludes_payments_with_null_amount_received(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Pago con amount_received (debe incluirse)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        // Pago con amount_received = 0 (debe incluirse)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '0.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::PAID,
        ]);

        // Pago sin amount_received (NO debe incluirse)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => null,
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::DEFAULT,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Solo debe incluir el pago con amount_received = 100.00
        $this->assertEquals('100.00', $result->totalPayments);

        // Solo debe haber un mes con pagos
        $this->assertCount(1, $result->paymentsByMonth);

        $monthKey = now()->format('Y-m');
        $this->assertArrayHasKey($monthKey, $result->paymentsByMonth);
        $this->assertEquals('100.00', $result->paymentsByMonth[$monthKey]);
    }

    #[Test]
    public function it_returns_zero_summary_when_no_payments_exist(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);
        $this->assertEquals('0.00', $result->totalPayments);
        $this->assertEmpty($result->paymentsByMonth);
        $this->assertIsArray($result->paymentsByMonth);
        $this->assertCount(0, $result->paymentsByMonth);
    }

    #[Test]
    public function it_returns_zero_summary_when_only_payments_with_null_amount_received(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Solo pagos sin amount_received
        PaymentModel::factory()->count(3)->create([
            'user_id' => $user->id,
            'amount_received' => null,
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::DEFAULT,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);
        $this->assertEquals('0.00', $result->totalPayments);
        $this->assertEmpty($result->paymentsByMonth);
    }

    #[Test]
    public function it_groups_payments_by_month_correctly(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Múltiples pagos en el mismo mes
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '50.00',
            'created_at' => Carbon::create(2024, 5, 1),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '75.00',
            'created_at' => Carbon::create(2024, 5, 15),
            'status' => PaymentStatus::PAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '125.00',
            'created_at' => Carbon::create(2024, 5, 31),
            'status' => PaymentStatus::OVERPAID,
        ]);

        // Pago en otro mes
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '300.00',
            'created_at' => Carbon::create(2024, 6, 10),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Total: 50 + 75 + 125 + 300 = 550.00
        $this->assertEquals('550.00', $result->totalPayments);

        // Solo 2 meses deben aparecer
        $this->assertCount(2, $result->paymentsByMonth);

        // Mayo: 50 + 75 + 125 = 250.00
        $this->assertArrayHasKey('2024-05', $result->paymentsByMonth);
        $this->assertEquals('250.00', $result->paymentsByMonth['2024-05']);

        // Junio: 300.00
        $this->assertArrayHasKey('2024-06', $result->paymentsByMonth);
        $this->assertEquals('300.00', $result->paymentsByMonth['2024-06']);
    }

    #[Test]
    public function it_orders_months_chronologically(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Crear pagos en orden no cronológico
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::create(2024, 3, 15),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '200.00',
            'created_at' => Carbon::create(2024, 1, 10),
            'status' => PaymentStatus::PAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '300.00',
            'created_at' => Carbon::create(2024, 2, 20),
            'status' => PaymentStatus::OVERPAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '400.00',
            'created_at' => Carbon::create(2023, 12, 5),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Debe tener 4 meses
        $this->assertCount(4, $result->paymentsByMonth);

        // Obtener claves de meses y verificar orden
        $months = array_keys($result->paymentsByMonth);

        // Orden debe ser: 2023-12, 2024-01, 2024-02, 2024-03
        $this->assertEquals('2023-12', $months[0]);
        $this->assertEquals('2024-01', $months[1]);
        $this->assertEquals('2024-02', $months[2]);
        $this->assertEquals('2024-03', $months[3]);
    }

    #[Test]
    public function it_includes_only_payments_for_specific_user(): void
    {
        // Arrange
        $user1 = UserModel::factory()->asStudent()->create();
        $user2 = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user1->assignRole($studentRole);
        $user2->assignRole($studentRole);

        // Pagos para user1
        PaymentModel::factory()->create([
            'user_id' => $user1->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user1->id,
            'amount_received' => '200.00',
            'created_at' => Carbon::now()->subMonth(),
            'status' => PaymentStatus::PAID,
        ]);

        // Pagos para user2
        PaymentModel::factory()->create([
            'user_id' => $user2->id,
            'amount_received' => '500.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $onlyThisYear = false;

        // Act - Para user1
        $resultUser1 = $this->useCase->execute($user1->id, $onlyThisYear);

        // Act - Para user2
        $resultUser2 = $this->useCase->execute($user2->id, $onlyThisYear);

        // Assert
        // User1: 100 + 200 = 300.00
        $this->assertEquals('300.00', $resultUser1->totalPayments);

        // User2: 500.00
        $this->assertEquals('500.00', $resultUser2->totalPayments);

        // Verificar que son diferentes
        $this->assertNotEquals($resultUser1->totalPayments, $resultUser2->totalPayments);
    }

    #[Test]
    public function it_handles_payments_with_zero_amount_received(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Pago con amount_received = 0
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '0.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::PAID,
        ]);

        // Pago con amount_received > 0
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Solo debe sumar 100.00 (el 0 no afecta)
        $this->assertEquals('100.00', $result->totalPayments);
    }

    #[Test]
    public function it_correctly_handles_decimal_amounts(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Pagos con decimales
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.50',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '200.75',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::PAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '150.25',
            'created_at' => Carbon::now()->subMonth(),
            'status' => PaymentStatus::OVERPAID,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Total: 100.50 + 200.75 + 150.25 = 451.50
        $this->assertEquals('451.50', $result->totalPayments);

        // Verificar meses individuales
        $currentMonth = now()->format('Y-m');
        $previousMonth = now()->subMonth()->format('Y-m');

        if (isset($result->paymentsByMonth[$currentMonth])) {
            // Mes actual: 100.50 + 200.75 = 301.25
            $this->assertEquals('301.25', $result->paymentsByMonth[$currentMonth]);
        }

        if (isset($result->paymentsByMonth[$previousMonth])) {
            // Mes anterior: 150.25
            $this->assertEquals('150.25', $result->paymentsByMonth[$previousMonth]);
        }
    }

    #[Test]
    public function it_ignores_payment_status_when_amount_received_exists(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Pagos con diferentes estados pero con amount_received
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '200.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::FAILED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '150.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::REQUIRES_ACTION,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '50.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::DEFAULT,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '75.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::UNDERPAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '25.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::UNPAID,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Debe sumar TODOS los pagos con amount_received: 100 + 200 + 150 + 50 + 75 + 25 = 600.00
        $this->assertEquals('600.00', $result->totalPayments);
    }

    #[Test]
    public function it_returns_correct_response_structure(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert - Estructura EXACTA del DTO
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);
        $this->assertObjectHasProperty('totalPayments', $result);
        $this->assertObjectHasProperty('paymentsByMonth', $result);

        // Verificar tipos de datos
        $this->assertIsString($result->totalPayments);
        $this->assertIsArray($result->paymentsByMonth);

        // Formato de total debe ser decimal con 2 decimales
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $result->totalPayments);

        // Formato de claves en paymentsByMonth
        if (!empty($result->paymentsByMonth)) {
            $firstKey = array_key_first($result->paymentsByMonth);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $firstKey);

            $firstValue = $result->paymentsByMonth[$firstKey];
            $this->assertIsString($firstValue);
            $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $firstValue);
        }
    }

    #[Test]
    public function it_handles_large_amounts_correctly(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Pagos con grandes cantidades
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '10000.00', // 1,000,000.00
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '25000.50', // 2,500,000.50
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::PAID,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        $this->assertEquals('35000.50', $result->totalPayments);
    }

    #[Test]
    public function it_handles_mixed_decimal_formats(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Pagos con diferentes formatos decimales
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.0', // Un solo decimal
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '200.00', // Dos decimales
            'created_at' => Carbon::now(),
            'status' => PaymentStatus::PAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '50.5', // Un solo decimal
            'created_at' => Carbon::now()->subMonth(),
            'status' => PaymentStatus::OVERPAID,
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // El resultado debe tener siempre 2 decimales
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $result->totalPayments);
    }

    #[Test]
    public function it_handles_only_this_year_with_edge_cases(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        $currentYear = now()->year;
        $lastYear = $currentYear - 1;
        $nextYear = $currentYear + 1;

        // Pago en el límite inferior del año actual (1 de enero)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '100.00',
            'created_at' => Carbon::create($currentYear, 1, 1, 0, 0, 0),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        // Pago en el límite superior del año actual (31 de diciembre)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '200.00',
            'created_at' => Carbon::create($currentYear, 12, 31, 23, 59, 59),
            'status' => PaymentStatus::PAID,
        ]);

        // Pago justo antes del año actual (31 de diciembre del año anterior, 23:59:59)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '300.00',
            'created_at' => Carbon::create($lastYear, 12, 31, 23, 59, 59),
            'status' => PaymentStatus::OVERPAID,
        ]);

        // Pago justo después del año actual (1 de enero del año siguiente, 00:00:00)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'amount_received' => '400.00',
            'created_at' => Carbon::create($nextYear, 1, 1, 0, 0, 0),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $onlyThisYear = true;

        // Act
        $result = $this->useCase->execute($user->id, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);

        // Solo deben incluirse los pagos del año actual: 100 + 200 = 300.00
        $this->assertEquals('300.00', $result->totalPayments);

        // Deben haber 2 meses (enero y diciembre)
        $this->assertCount(2, $result->paymentsByMonth);

        $this->assertArrayHasKey($currentYear . '-01', $result->paymentsByMonth);
        $this->assertArrayHasKey($currentYear . '-12', $result->paymentsByMonth);
    }

}
