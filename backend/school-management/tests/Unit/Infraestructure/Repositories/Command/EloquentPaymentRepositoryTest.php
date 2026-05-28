<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Domain\Entities\Payment;
use App\Core\Infraestructure\Mappers\PaymentMapper;
use App\Models\Payment as EloquentPayment;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentPaymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentPaymentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPaymentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentPaymentRepository();
    }

    // ==================== CREATE TESTS ====================

    #[Test]
    public function create_payment_successfully(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $paymentConcept = \App\Models\PaymentConcept::factory()->create();
        $paymentMethod = \App\Models\PaymentMethod::factory()->create();

        $payment = new Payment(
            concept_name: 'Test Payment',
            amount: '1500.50',
            status: PaymentStatus::DEFAULT,
            payment_method_details: ['card' => ['brand' => 'Visa']],
            user_id: $user->id,
            payment_concept_id: $paymentConcept->id,
            payment_method_id: $paymentMethod->id,
            stripe_payment_method_id: 'pm_test_123',
            amount_received: null,
            payment_intent_id: 'pi_test_123',
            url: 'https://example.com/pay',
            stripe_session_id: 'cs_test_123'
        );

        // Act
        $result = $this->repository->create($payment);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals('Test Payment', $result->concept_name);
        $this->assertEquals('1500.50', $result->amount);
        $this->assertEquals(PaymentStatus::DEFAULT, $result->status);
        $this->assertEquals(['card' => ['brand' => 'Visa']], $result->payment_method_details);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals($paymentConcept->id, $result->payment_concept_id);
        $this->assertEquals($paymentMethod->id, $result->payment_method_id);
        $this->assertEquals('pm_test_123', $result->stripe_payment_method_id);
        $this->assertNull($result->amount_received);
        $this->assertEquals('pi_test_123', $result->payment_intent_id);
        $this->assertEquals('https://example.com/pay', $result->url);
        $this->assertEquals('cs_test_123', $result->stripe_session_id);
        $this->assertNotNull($result->created_at);

        $this->assertDatabaseHas('payments', [
            'concept_name' => 'Test Payment',
            'amount' => '1500.50',
            'status' => PaymentStatus::DEFAULT->value,
            'user_id' => $user->id,
            'payment_concept_id' => $paymentConcept->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
    }

    #[Test]
    public function create_payment_with_minimal_data(): void
    {
        // Arrange
        $payment = new Payment(
            concept_name: 'Minimal Payment',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT
        );

        // Act
        $result = $this->repository->create($payment);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals('Minimal Payment', $result->concept_name);
        $this->assertEquals('1000.00', $result->amount);
        $this->assertEquals(PaymentStatus::DEFAULT, $result->status);
        $this->assertEmpty($result->payment_method_details);
        $this->assertNull($result->user_id);
        $this->assertNull($result->payment_concept_id);
        $this->assertNull($result->payment_method_id);
        $this->assertNull($result->stripe_payment_method_id);
        $this->assertNull($result->amount_received);
        $this->assertNull($result->payment_intent_id);
        $this->assertNull($result->url);
        $this->assertNull($result->stripe_session_id);

        $this->assertDatabaseHas('payments', [
            'concept_name' => 'Minimal Payment',
            'amount' => '1000.00',
            'status' => PaymentStatus::DEFAULT->value,
        ]);
    }

    #[Test]
    public function create_payment_with_factory(): void
    {
        // Arrange
        $paymentData = EloquentPayment::factory()->make();
        $domainPayment = PaymentMapper::toDomain($paymentData);

        // Act
        $result = $this->repository->create($domainPayment);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals($paymentData->concept_name, $result->concept_name);
        $this->assertEquals($paymentData->amount, $result->amount);
        $this->assertEquals($paymentData->status, $result->status);
        $this->assertEquals($paymentData->payment_method_details, $result->payment_method_details);
        $this->assertEquals($paymentData->user_id, $result->user_id);
        $this->assertEquals($paymentData->payment_concept_id, $result->payment_concept_id);

        $this->assertDatabaseHas('payments', [
            'concept_name' => $paymentData->concept_name,
            'amount' => $paymentData->amount,
            'status' => $paymentData->status->value,
        ]);
    }

    #[Test]
    public function create_payment_with_different_statuses(): void
    {
        // Arrange
        $statuses = [
            PaymentStatus::DEFAULT,
            PaymentStatus::SUCCEEDED,
            PaymentStatus::UNDERPAID,
            PaymentStatus::OVERPAID,
            PaymentStatus::UNPAID,
        ];

        foreach ($statuses as $status) {
            $payment = new Payment(
                concept_name: "Payment {$status->value}",
                amount: '1000.00',
                status: $status
            );

            // Act
            $result = $this->repository->create($payment);

            // Assert
            $this->assertEquals($status, $result->status);
            $this->assertDatabaseHas('payments', [
                'concept_name' => "Payment {$status->value}",
                'status' => $status->value,
            ]);
        }
    }

    #[Test]
    public function create_multiple_payments(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();

        $payment1 = new Payment(
            concept_name: 'Payment 1',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT,
            user_id: $user->id
        );

        $payment2 = new Payment(
            concept_name: 'Payment 2',
            amount: '2000.00',
            status: PaymentStatus::SUCCEEDED,
            user_id: $user->id
        );

        // Act
        $result1 = $this->repository->create($payment1);
        $result2 = $this->repository->create($payment2);

        // Assert
        $this->assertNotEquals($result1->id, $result2->id);

        $paymentCount = EloquentPayment::where('user_id', $user->id)->count();
        $this->assertEquals(2, $paymentCount);
    }

    #[Test]
    public function create_payment_with_json_payment_method_details(): void
    {
        // Arrange
        $paymentDetails = [
            'card' => [
                'brand' => 'MasterCard',
                'last4' => '5555',
                'exp_month' => 12,
                'exp_year' => 2026,
                'country' => 'MX',
            ]
        ];

        $payment = new Payment(
            concept_name: 'Card Payment',
            amount: '2500.00',
            status: PaymentStatus::SUCCEEDED,
            payment_method_details: $paymentDetails
        );

        // Act
        $result = $this->repository->create($payment);

        // Assert
        $this->assertEquals($paymentDetails, $result->payment_method_details);

        $dbRecord = EloquentPayment::find($result->id);
        $this->assertNotNull($dbRecord);
        $this->assertEquals($paymentDetails, $dbRecord->payment_method_details);
    }

    // ==================== UPDATE TESTS ====================

    #[Test]
    public function update_payment_successfully(): void
    {
        // Arrange
        $payment = EloquentPayment::factory()->create();
        $updateData = [
            'concept_name' => 'Updated Concept',
            'amount' => '3000.75',
            'status' => PaymentStatus::SUCCEEDED,
            'amount_received' => '3000.75',
        ];

        // Act
        $result = $this->repository->update($payment->id, $updateData);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($payment->id, $result->id);
        $this->assertEquals('Updated Concept', $result->concept_name);
        $this->assertEquals('3000.75', $result->amount);
        $this->assertEquals(PaymentStatus::SUCCEEDED, $result->status);
        $this->assertEquals('3000.75', $result->amount_received);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'concept_name' => 'Updated Concept',
            'amount' => '3000.75',
            'status' => PaymentStatus::SUCCEEDED->value,
            'amount_received' => '3000.75',
        ]);
    }

    #[Test]
    public function update_payment_partial_fields(): void
    {
        // Arrange
        $payment = EloquentPayment::factory()->create([
            'concept_name' => 'Original Concept',
            'amount' => '1000.00',
            'status' => PaymentStatus::DEFAULT,
        ]);

        $updateData = [
            'status' => PaymentStatus::SUCCEEDED,
            'amount_received' => '1000.00',
        ];

        // Act
        $result = $this->repository->update($payment->id, $updateData);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($payment->id, $result->id);
        $this->assertEquals('Original Concept', $result->concept_name); // No cambió
        $this->assertEquals('1000.00', $result->amount); // No cambió
        $this->assertEquals(PaymentStatus::SUCCEEDED, $result->status); // Cambió
        $this->assertEquals('1000.00', $result->amount_received); // Cambió

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'concept_name' => 'Original Concept',
            'amount' => '1000.00',
            'status' => PaymentStatus::SUCCEEDED->value,
            'amount_received' => '1000.00',
        ]);
    }

    #[Test]
    public function update_payment_status_transitions(): void
    {
        // Arrange
        $payment = EloquentPayment::factory()->state(['status' => PaymentStatus::DEFAULT])->create();

        // Test multiple status transitions
        $transitions = [
            PaymentStatus::SUCCEEDED,
            PaymentStatus::UNDERPAID,
            PaymentStatus::OVERPAID,
            PaymentStatus::UNPAID,
        ];

        foreach ($transitions as $newStatus) {
            // Act
            $result = $this->repository->update($payment->id, ['status' => $newStatus]);

            // Assert
            $this->assertEquals($newStatus, $result->status);
            $this->assertDatabaseHas('payments', [
                'id' => $payment->id,
                'status' => $newStatus->value,
            ]);
        }
    }

    #[Test]
    public function update_payment_with_json_fields(): void
    {
        // Arrange
        $payment = EloquentPayment::factory()->create();

        $newDetails = [
            'card_present' => [
                'brand' => 'Visa',
                'last4' => '1234',
                'read_method' => 'contactless',
            ]
        ];

        $updateData = [
            'payment_method_details' => $newDetails,
        ];

        // Act
        $result = $this->repository->update($payment->id, $updateData);

        // Assert
        $this->assertEquals($newDetails, $result->payment_method_details);

        $dbRecord = EloquentPayment::find($payment->id);
        $this->assertEquals($newDetails, $dbRecord->payment_method_details);
    }

    #[Test]
    public function update_nonexistent_payment_throws_exception(): void
    {
        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->update(999999, ['status' => PaymentStatus::SUCCEEDED]);
    }

    #[Test]
    public function update_payment_with_empty_array_does_not_change(): void
    {
        // Arrange
        $payment = EloquentPayment::factory()->create([
            'concept_name' => 'Original',
            'status' => PaymentStatus::DEFAULT,
        ]);

        // Act
        $result = $this->repository->update($payment->id, []);

        // Assert
        $this->assertEquals('Original', $result->concept_name);
        $this->assertEquals(PaymentStatus::DEFAULT, $result->status);
    }

    // ==================== DELETE TESTS ====================

    #[Test]
    public function delete_payment_successfully(): void
    {
        // Arrange
        $payment = EloquentPayment::factory()->create();

        // Act
        $this->repository->delete($payment->id);

        // Assert
        $this->assertDatabaseMissing('payments', [
            'id' => $payment->id,
        ]);
    }

    #[Test]
    public function delete_nonexistent_payment_throws_exception(): void
    {
        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->delete(999999);
    }

    #[Test]
    public function delete_payment_removes_only_specific_record(): void
    {
        // Arrange
        $payment1 = EloquentPayment::factory()->create();
        $payment2 = EloquentPayment::factory()->create();
        $payment3 = EloquentPayment::factory()->create();

        // Act
        $this->repository->delete($payment2->id);

        // Assert
        $this->assertDatabaseMissing('payments', [
            'id' => $payment2->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment1->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment3->id,
        ]);
    }

    // ==================== DOMAIN LOGIC TESTS ====================

    #[Test]
    public function payment_calculates_pending_amount_correctly(): void
    {
        // Arrange
        $payment = new Payment(
            concept_name: 'Test',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT,
            amount_received: '600.00'
        );

        // Act & Assert
        $this->assertEquals('400.00', $payment->getPendingAmount());
    }

    #[Test]
    public function payment_calculates_zero_pending_when_fully_paid(): void
    {
        // Arrange
        $payment = new Payment(
            concept_name: 'Test',
            amount: '1000.00',
            status: PaymentStatus::SUCCEEDED,
            amount_received: '1000.00'
        );

        // Act & Assert
        $this->assertEquals('0.00', $payment->getPendingAmount());
    }

    #[Test]
    public function payment_calculates_overpaid_amount(): void
    {
        // Arrange
        $payment = new Payment(
            concept_name: 'Test',
            amount: '1000.00',
            status: PaymentStatus::OVERPAID,
            amount_received: '1200.00'
        );

        // Act & Assert
        $this->assertEquals('200.00', $payment->getOverPaidAmount());
    }

    #[Test]
    public function payment_is_overpaid_check(): void
    {
        // Arrange
        $overpaidPayment = new Payment(
            concept_name: 'Test',
            amount: '1000.00',
            status: PaymentStatus::OVERPAID
        );

        $notOverpaidPayment = new Payment(
            concept_name: 'Test',
            amount: '1000.00',
            status: PaymentStatus::SUCCEEDED
        );

        // Act & Assert
        $this->assertTrue($overpaidPayment->isOverPaid());
        $this->assertFalse($notOverpaidPayment->isOverPaid());
    }

    #[Test]
    public function payment_is_underpaid_check(): void
    {
        // Arrange
        $underpaidPayment = new Payment(
            concept_name: 'Test',
            amount: '1000.00',
            status: PaymentStatus::UNDERPAID
        );

        $notUnderpaidPayment = new Payment(
            concept_name: 'Test',
            amount: '1000.00',
            status: PaymentStatus::SUCCEEDED
        );

        // Act & Assert
        $this->assertTrue($underpaidPayment->isUnderPaid());
        $this->assertFalse($notUnderpaidPayment->isUnderPaid());
    }

    #[Test]
    public function payment_is_non_paid_check(): void
    {
        // Non-paid statuses según PaymentStatus::nonPaidStatuses()
        $nonPaidStatuses = [
            PaymentStatus::DEFAULT,
            PaymentStatus::UNPAID,
            PaymentStatus::REQUIRES_ACTION
        ];

        $paidStatuses = [
            PaymentStatus::SUCCEEDED,
            PaymentStatus::OVERPAID,
        ];

        foreach ($nonPaidStatuses as $status) {
            $payment = new Payment(
                concept_name: 'Test',
                amount: '1000.00',
                status: $status
            );
            $this->assertTrue($payment->isNonPaid(), "Status {$status->value} should be non-paid");
        }

        foreach ($paidStatuses as $status) {
            $payment = new Payment(
                concept_name: 'Test',
                amount: '1000.00',
                status: $status
            );
            $this->assertFalse($payment->isNonPaid(), "Status {$status->value} should not be non-paid");
        }
    }

    #[Test]
    public function payment_is_recent_check(): void
    {
        // Arrange
        $recentPayment = new Payment(
            concept_name: 'Recent',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT,
            created_at: now()->subMinutes(30) // Menos de una hora
        );

        $oldPayment = new Payment(
            concept_name: 'Old',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT,
            created_at: now()->subHours(2) // Más de una hora
        );

        // Act & Assert
        $this->assertTrue($recentPayment->isRecentPayment());
        $this->assertFalse($oldPayment->isRecentPayment());
    }

    // ==================== INTEGRATION TESTS ====================

    #[Test]
    public function create_update_delete_integration(): void
    {
        // Test completo del ciclo de vida

        // Arrange
        $user = \App\Models\User::factory()->create();

        $initialPayment = new Payment(
            concept_name: 'Initial Payment',
            amount: '500.00',
            status: PaymentStatus::DEFAULT,
            user_id: $user->id
        );

        // Act 1: Create
        $created = $this->repository->create($initialPayment);

        // Assert 1
        $this->assertDatabaseHas('payments', [
            'id' => $created->id,
            'status' => PaymentStatus::DEFAULT->value,
        ]);

        // Act 2: Update
        $updated = $this->repository->update($created->id, [
            'status' => PaymentStatus::SUCCEEDED,
            'amount_received' => '500.00',
        ]);

        // Assert 2
        $this->assertEquals(PaymentStatus::SUCCEEDED, $updated->status);
        $this->assertEquals('500.00', $updated->amount_received);
        $this->assertEquals('0.00', $updated->getPendingAmount());

        // Act 3: Delete
        $this->repository->delete($created->id);

        // Assert 3
        $this->assertDatabaseMissing('payments', [
            'id' => $created->id,
        ]);
    }

    #[Test]
    public function multiple_operations_with_factory_states(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $paymentMethod = \App\Models\PaymentMethod::factory()->create();

        // Crear pagos directamente en la base de datos
        $pendingPayment = EloquentPayment::factory()->pending()->forUser($user)->create([
            'stripe_session_id' => 'cs_pending_' . \Illuminate\Support\Str::random(20)
        ]);

        $completedPayment = EloquentPayment::factory()->completed()->forUser($user)->create([
            'stripe_session_id' => 'cs_completed_' . \Illuminate\Support\Str::random(20)
        ]);

        // Obtener los domain objects desde los modelos ya creados
        $pendingDomain = PaymentMapper::toDomain($pendingPayment);
        $completedDomain = PaymentMapper::toDomain($completedPayment);

        // Assert estado inicial
        $this->assertEquals(PaymentStatus::DEFAULT, $pendingDomain->status);
        $this->assertNull($pendingDomain->amount_received);

        $this->assertEquals(PaymentStatus::SUCCEEDED, $completedDomain->status);
        $this->assertEquals($completedDomain->amount, $completedDomain->amount_received);

        // Act: Update pending to completed
        $updatedPending = $this->repository->update($pendingPayment->id, [
            'status' => PaymentStatus::SUCCEEDED,
            'amount_received' => $pendingPayment->amount,
        ]);

        // Assert
        $this->assertEquals(PaymentStatus::SUCCEEDED, $updatedPending->status);
        $this->assertEquals($pendingPayment->amount, $updatedPending->amount_received);

        // Act: Delete both
        $this->repository->delete($pendingPayment->id);
        $this->repository->delete($completedPayment->id);

        // Assert
        $this->assertDatabaseMissing('payments', ['id' => $pendingPayment->id]);
        $this->assertDatabaseMissing('payments', ['id' => $completedPayment->id]);
    }

    #[Test]
    public function payment_with_factory_specific_methods(): void
    {
        // Test con diferentes factory states

        $paymentTypes = [
            ['factory' => fn() => EloquentPayment::factory()->tuition(), 'expected' => 'Colegiatura Mensual'],
            ['factory' => fn() => EloquentPayment::factory()->enrollment(), 'expected' => 'Inscripción Semestral'],
            ['factory' => fn() => EloquentPayment::factory()->materials(), 'expected' => 'Material Didáctico'],
            ['factory' => fn() => EloquentPayment::factory()->exam(), 'expected' => 'Examen de Admisión'],
        ];

        foreach ($paymentTypes as $type) {
            // Arrange
            $paymentData = $type['factory']()->make();
            $domainPayment = PaymentMapper::toDomain($paymentData);

            // Act
            $result = $this->repository->create($domainPayment);

            // Assert
            $this->assertEquals($type['expected'], $result->concept_name);

            // Cleanup
            $this->repository->delete($result->id);
        }
    }

    #[Test]
    public function payment_amount_calculations_after_update(): void
    {
        // Arrange
        $payment = EloquentPayment::factory()->state([
            'amount' => '1000.00',
            'amount_received' => '600.00',
            'status' => PaymentStatus::UNDERPAID,
        ])->create();

        $domainPayment = PaymentMapper::toDomain($payment);

        // Assert initial state
        $this->assertEquals('400.00', $domainPayment->getPendingAmount());
        $this->assertEquals('0.00', $domainPayment->getOverPaidAmount());
        $this->assertTrue($domainPayment->isUnderPaid());

        // Act: Update to fully paid
        $updated = $this->repository->update($payment->id, [
            'amount_received' => '1000.00',
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        // Assert after update
        $this->assertEquals('0.00', $updated->getPendingAmount());
        $this->assertEquals('0.00', $updated->getOverPaidAmount());
        $this->assertFalse($updated->isUnderPaid());
        $this->assertFalse($updated->isOverPaid());
    }

    #[Test]
    public function repository_handles_concurrent_operations(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();

        // Crear 3 pagos
        $payments = [];
        for ($i = 1; $i <= 3; $i++) {
            $payment = new Payment(
                concept_name: "Payment {$i}",
                amount: (string)($i * 1000),
                status: PaymentStatus::DEFAULT,
                user_id: $user->id
            );
            $payments[] = $this->repository->create($payment);
        }

        // Act: Operaciones concurrentes
        // Update primero
        $this->repository->update($payments[0]->id, ['status' => PaymentStatus::SUCCEEDED]);
        // Delete segundo
        $this->repository->delete($payments[1]->id);
        // Update tercero
        $this->repository->update($payments[2]->id, ['status' => PaymentStatus::UNDERPAID]);

        // Assert
        $this->assertDatabaseHas('payments', [
            'id' => $payments[0]->id,
            'status' => PaymentStatus::SUCCEEDED->value,
        ]);

        $this->assertDatabaseMissing('payments', [
            'id' => $payments[1]->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payments[2]->id,
            'status' => PaymentStatus::UNDERPAID->value,
        ]);

        $remainingCount = EloquentPayment::where('user_id', $user->id)->count();
        $this->assertEquals(2, $remainingCount);
    }

}
