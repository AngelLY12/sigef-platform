<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Students\Dashboard;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\UseCases\Payments\Student\Dashboard\PaymentHistoryUseCase;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;
use App\Models\Payment as PaymentModel;

class PaymentHistoryUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private PaymentHistoryUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        // Verificar que estamos en entorno testing
        $this->assertEquals('testing', config('app.env'),
            'Tests must run in testing environment');

        $this->useCase = app(PaymentHistoryUseCase::class);
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function it_returns_paginated_payment_history(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Crear pagos para el usuario
        PaymentModel::factory()->count(15)->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $perPage = 10;
        $page = 1;
        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $perPage, $page, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaginatedResponse::class, $result);
        $this->assertCount($perPage, $result->items);
        $this->assertEquals(1, $result->currentPage);
        $this->assertEquals(2, $result->lastPage);
        $this->assertEquals($perPage, $result->perPage);
        $this->assertEquals(15, $result->total);
        $this->assertTrue($result->hasMorePages);
        $this->assertEquals(2, $result->nextPage);
        $this->assertNull($result->previousPage);

        // Verificar estructura de los items
        $firstItem = $result->items[0];
        $this->assertObjectHasProperty('id', $firstItem);
        $this->assertObjectHasProperty('concept', $firstItem);
        $this->assertObjectHasProperty('amount', $firstItem);
        $this->assertObjectHasProperty('amount_received', $firstItem);
        $this->assertObjectHasProperty('status', $firstItem);
        $this->assertObjectHasProperty('date', $firstItem);
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

        // Pago del año actual
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create($currentYear, 6, 15),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        // Pago del año anterior
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create($lastYear, 6, 15),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $perPage = 10;
        $page = 1;
        $onlyThisYear = true;

        // Act
        $result = $this->useCase->execute($user->id, $perPage, $page, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaginatedResponse::class, $result);
        $this->assertCount(1, $result->items);
        $this->assertEquals(1, $result->total);

        // Verificar que el pago mostrado es del año actual
        $paymentDate = Carbon::parse($result->items[0]->date);
        $this->assertEquals($currentYear, $paymentDate->year);
    }

    #[Test]
    public function it_returns_all_payments_when_onlyThisYear_is_false(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        $currentYear = now()->year;
        $lastYear = $currentYear - 1;

        // Crear pagos de diferentes años
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create($currentYear, 6, 15),
            'status' => PaymentStatus::OVERPAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create($lastYear, 3, 10),
            'status' => PaymentStatus::UNDERPAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create($lastYear, 11, 20),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $perPage = 10;
        $page = 1;
        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $perPage, $page, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaginatedResponse::class, $result);
        $this->assertCount(3, $result->items);
        $this->assertEquals(3, $result->total);
    }

    #[Test]
    public function it_returns_empty_paginated_response_when_no_payments_exist(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        $perPage = 10;
        $page = 1;
        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $perPage, $page, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaginatedResponse::class, $result);
        $this->assertEmpty($result->items);
        $this->assertEquals(0, $result->total);
        $this->assertEquals(1, $result->currentPage);
        $this->assertEquals(1, $result->lastPage);
        $this->assertFalse($result->hasMorePages);
    }

    #[Test]
    public function it_returns_correct_pagination_for_different_pages(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Crear 25 pagos
        PaymentModel::factory()->count(25)->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $perPage = 10;

        // Act - Página 1
        $resultPage1 = $this->useCase->execute($user->id, $perPage, 1, false);

        // Act - Página 2
        $resultPage2 = $this->useCase->execute($user->id, $perPage, 2, false);

        // Act - Página 3
        $resultPage3 = $this->useCase->execute($user->id, $perPage, 3, false);

        // Assert
        $this->assertCount(10, $resultPage1->items);
        $this->assertEquals(1, $resultPage1->currentPage);
        $this->assertTrue($resultPage1->hasMorePages);
        $this->assertEquals(2, $resultPage1->nextPage);
        $this->assertNull($resultPage1->previousPage);

        $this->assertCount(10, $resultPage2->items);
        $this->assertEquals(2, $resultPage2->currentPage);
        $this->assertTrue($resultPage2->hasMorePages);
        $this->assertEquals(3, $resultPage2->nextPage);
        $this->assertEquals(1, $resultPage2->previousPage);

        $this->assertCount(5, $resultPage3->items); // Última página con 5 items
        $this->assertEquals(3, $resultPage3->currentPage);
        $this->assertFalse($resultPage3->hasMorePages);
        $this->assertNull($resultPage3->nextPage);
        $this->assertEquals(2, $resultPage3->previousPage);
    }

    #[Test]
    public function it_returns_payments_ordered_by_created_at_desc(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Crear pagos con fechas específicas
        $oldest = PaymentModel::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create(2024, 1, 1, 10, 0, 0),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $middle = PaymentModel::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create(2024, 6, 1, 14, 30, 0),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $newest = PaymentModel::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create(2024, 12, 1, 9, 15, 0),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $perPage = 10;
        $page = 1;
        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $perPage, $page, $onlyThisYear);

        // Assert - Orden descendente por fecha de creación
        $this->assertCount(3, $result->items);

        // El primero debe ser el más reciente
        $this->assertEquals($newest->id, $result->items[0]->id);

        // El segundo debe ser el del medio
        $this->assertEquals($middle->id, $result->items[1]->id);

        // El último debe ser el más antiguo
        $this->assertEquals($oldest->id, $result->items[2]->id);
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
        PaymentModel::factory()->count(3)->create([
            'user_id' => $user1->id,
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        // Pagos para user2
        PaymentModel::factory()->count(5)->create([
            'user_id' => $user2->id,
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $perPage = 10;
        $page = 1;
        $onlyThisYear = false;

        // Act - Para user1
        $resultUser1 = $this->useCase->execute($user1->id, $perPage, $page, $onlyThisYear);

        // Act - Para user2
        $resultUser2 = $this->useCase->execute($user2->id, $perPage, $page, $onlyThisYear);

        // Assert
        $this->assertCount(3, $resultUser1->items);
        $this->assertEquals(3, $resultUser1->total);

        $this->assertCount(5, $resultUser2->items);
        $this->assertEquals(5, $resultUser2->total);

        // Verificar que los IDs de usuario coinciden
        foreach ($resultUser1->items as $item) {
            $payment = PaymentModel::find($item->id);
            $this->assertEquals($user1->id, $payment->user_id);
        }
    }

    #[Test]
    public function it_handles_different_payment_statuses(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Crear pagos con diferentes estados
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::DEFAULT,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::PAID,
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::REQUIRES_ACTION,
        ]);

        $perPage = 10;
        $page = 1;
        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $perPage, $page, $onlyThisYear);

        // Assert
        $this->assertCount(4, $result->items);

        // Verificar que todos los estados están presentes
        $statuses = collect($result->items)->pluck('status')->unique()->toArray();
        sort($statuses);

        $expectedStatuses = [
            PaymentStatus::DEFAULT->value,
            PaymentStatus::SUCCEEDED->value,
            PaymentStatus::PAID->value,
            PaymentStatus::REQUIRES_ACTION->value,
        ];
        sort($expectedStatuses);

        $this->assertEquals($expectedStatuses, $statuses);
    }

    #[Test]
    public function it_returns_correct_data_structure_for_each_payment(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        $payment = PaymentModel::factory()->create([
            'user_id' => $user->id,
            'concept_name' => 'Test Payment Concept',
            'amount' => '100.00', // 100.00 en centavos
            'amount_received' => '98.00', // 98.00 en centavos
            'status' => PaymentStatus::UNDERPAID,
            'created_at' => Carbon::create(2024, 12, 15, 14, 30, 45),
        ]);

        $perPage = 10;
        $page = 1;
        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($user->id, $perPage, $page, $onlyThisYear);

        // Assert
        $this->assertCount(1, $result->items);

        $item = $result->items[0];

        $this->assertEquals($payment->id, $item->id);
        $this->assertEquals('Test Payment Concept', $item->concept);
        $this->assertEquals('100.00', $item->amount);
        $this->assertEquals('98.00', $item->amount_received);
        $this->assertEquals(PaymentStatus::UNDERPAID->value, $item->status);
        $this->assertEquals('2024-12-15 14:30:45', $item->date);
    }

    #[Test]
    public function it_handles_zero_payments_for_current_year(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        // Pago del año anterior
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create(now()->year - 1, 6, 15),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $perPage = 10;
        $page = 1;
        $onlyThisYear = true;

        // Act
        $result = $this->useCase->execute($user->id, $perPage, $page, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PaginatedResponse::class, $result);
        $this->assertEmpty($result->items);
        $this->assertEquals(0, $result->total);
    }

    #[Test]
    public function it_maintains_correct_pagination_with_onlyThisYear_filter(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user->assignRole($studentRole);

        $currentYear = now()->year;

        // 15 pagos del año actual
        PaymentModel::factory()->count(15)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create($currentYear, 6, 15),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        // 10 pagos del año anterior (no deben contarse)
        PaymentModel::factory()->count(10)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::create($currentYear - 1, 6, 15),
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $perPage = 10;
        $onlyThisYear = true;

        // Act - Página 1
        $resultPage1 = $this->useCase->execute($user->id, $perPage, 1, $onlyThisYear);

        // Act - Página 2
        $resultPage2 = $this->useCase->execute($user->id, $perPage, 2, $onlyThisYear);

        // Assert
        $this->assertCount(10, $resultPage1->items);
        $this->assertEquals(15, $resultPage1->total);
        $this->assertTrue($resultPage1->hasMorePages);

        $this->assertCount(5, $resultPage2->items);
        $this->assertFalse($resultPage2->hasMorePages);
    }

}
