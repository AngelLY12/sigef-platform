<?php

namespace Tests\Unit\Application\Mappers;

use App\Core\Application\DTO\Response\General\ReconciliationResult;
use App\Core\Application\DTO\Response\Payment\FinancialSummaryResponse;
use App\Core\Application\DTO\Response\Payment\PaymentDataResponse;
use App\Core\Application\DTO\Response\Payment\PaymentDetailResponse;
use App\Core\Application\DTO\Response\Payment\PaymentHistoryResponse;
use App\Core\Application\DTO\Response\Payment\PaymentListItemResponse;
use App\Core\Application\DTO\Response\Payment\PaymentsMadeByConceptName;
use App\Core\Application\DTO\Response\Payment\PaymentsSummaryResponse;
use App\Core\Application\DTO\Response\Payment\PaymentValidateResponse;
use App\Core\Application\DTO\Response\User\UserDataResponse;
use App\Core\Application\Mappers\EnumMapper;
use App\Core\Application\Mappers\PaymentMapper;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Models\Payment;
use Carbon\Carbon;
use App\Core\Domain\Entities\Payment as DomainPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Stripe\Checkout\Session;
use Tests\TestCase;

class PaymentMapperTest extends TestCase
{
    use RefreshDatabase;

    // ==================== TO DOMAIN TESTS ====================

    #[Test]
    public function to_domain_creates_domain_payment_from_concept_and_session(): void
    {
        // Arrange
        $concept = $this->createMock(PaymentConcept::class);
        $concept->concept_name = 'Tuition Fee';
        $concept->amount = '500.00';
        $concept->id = 123;

        $userId = 456;

        $session = $this->createStripeSession(
            [
                'payment_status' => 'paid',
                'id' => 'cs_test_123',
                'url' => 'https://checkout.stripe.com/test_123'
            ]
        );


        // Act
        $result = PaymentMapper::toDomain($concept, $userId, $session);

        // Assert
        $this->assertInstanceOf(DomainPayment::class, $result);
        $this->assertEquals('Tuition Fee', $result->concept_name);
        $this->assertEquals('500.00', $result->amount);
        // Nota: PaymentStatus es un enum, no podemos comparar directamente con 'paid'
        $this->assertEquals(456, $result->user_id);
        $this->assertEquals(123, $result->payment_concept_id);
        $this->assertEquals('https://checkout.stripe.com/test_123', $result->url);
        $this->assertEquals('cs_test_123', $result->stripe_session_id);
    }

    #[Test]
    public function to_domain_with_null_session_values(): void
    {
        // Arrange
        $concept = $this->createMock(PaymentConcept::class);
        $concept->concept_name = 'Test Concept';
        $concept->amount = '100.00';
        $concept->id = 1;

        $userId = 789;

        $session = $this->createStripeSession(
            [
                'payment_status' => 'unpaid',
                'id' => null,
                'url' => null
            ]
        );

        // Act
        $result = PaymentMapper::toDomain($concept, $userId, $session);

        // Assert
        $this->assertInstanceOf(DomainPayment::class, $result);
        $this->assertEquals('Test Concept', $result->concept_name);
        $this->assertEquals('100.00', $result->amount);
        $this->assertNull($result->url);
        $this->assertNull($result->stripe_session_id);
    }

    // ==================== TO HISTORY RESPONSE TESTS ====================

    #[Test]
    public function to_history_response_creates_response_from_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'concept_name' => 'Annual Fee',
            'amount' => '250.75',
            'amount_received' => '250.75',
            'status' => PaymentStatus::PAID, // Cambiar según tu enum
            'created_at' => '2024-01-15 10:30:00',
        ]);

        // Act
        $result = PaymentMapper::toHistoryResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentHistoryResponse::class, $result);
        $this->assertEquals($payment->id, $result->id);
        $this->assertEquals('Annual Fee', $result->concept);
        $this->assertEquals('250.75', $result->amount);
        $this->assertEquals('250.75', $result->amount_received);
        $this->assertEquals('paid', $result->status); // Valor del enum
        $this->assertEquals('2024-01-15 10:30:00', $result->date);
    }

    #[Test]
    public function to_history_response_with_null_values(): void
    {
        // Arrange
        $payment = new Payment();
        $payment->concept_name = null;
        $payment->amount = null;
        $payment->amount_received = null;
        $payment->status = null;
        $payment->created_at = null;

        // Act
        $result = PaymentMapper::toHistoryResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentHistoryResponse::class, $result);
        $this->assertNull($result->id);
        $this->assertNull($result->concept);
        $this->assertNull($result->amount);
        $this->assertNull($result->amount_received);
        $this->assertNull($result->status);
        $this->assertNull($result->date);
    }

    // ==================== TO DETAIL RESPONSE TESTS ====================

    #[Test]
    public function to_detail_response_creates_response_from_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'concept_name' => 'Library Fee',
            'amount' => '150.00',
            'amount_received' => '150.00',
            'status' => PaymentStatus::PAID,
            'created_at' => '2024-02-20 14:45:00',
            'payment_intent_id' => 'pi_123456789',
            'url' => 'https://receipt.example.com/123',
            'payment_method_details' => ['type' => 'card', 'last4' => '4242'],
        ]);

        // Act
        $result = PaymentMapper::toDetailResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentDetailResponse::class, $result);
        $this->assertEquals($payment->id, $result->id);
        $this->assertEquals('Library Fee', $result->concept);
        $this->assertEquals('150.00', $result->amount);
        $this->assertEquals('150.00', $result->amount_received);
        // $result->balance puede ser null o un valor dependiendo de la lógica
        $this->assertEquals('2024-02-20 14:45:00', $result->date);
        $this->assertEquals('paid', $result->status);
        $this->assertEquals('pi_123456789', $result->reference);
        $this->assertEquals('https://receipt.example.com/123', $result->url);
        $this->assertEquals(['type' => 'card', 'last4' => '4242'], $result->payment_method_details);
    }

    #[Test]
    public function to_detail_response_with_overpaid_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'amount' => '100.00',
            'amount_received' => '150.00',
            'status' => PaymentStatus::PAID,
        ]);

        // Act
        $result = PaymentMapper::toDetailResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentDetailResponse::class, $result);
        // El balance debería ser calculado por el mapper
    }

    #[Test]
    public function to_detail_response_with_underpaid_payment(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'amount' => '200.00',
            'amount_received' => '150.00',
            'status' => PaymentStatus::UNDERPAID,
        ]);

        // Act
        $result = PaymentMapper::toDetailResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentDetailResponse::class, $result);
        // El balance debería ser calculado por el mapper
    }

    #[Test]
    public function to_detail_response_with_null_values(): void
    {
        // Arrange
        $payment = new Payment();
        $payment->id = 1;
        $payment->concept_name = 'Test';
        $payment->amount = '100.00';
        $payment->amount_received = null;
        $payment->status = PaymentStatus::PAID;
        $payment->created_at = null;
        $payment->payment_intent_id = null;
        $payment->url = null;
        $payment->payment_method_details = null;

        // Act
        $result = PaymentMapper::toDetailResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentDetailResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test', $result->concept);
        $this->assertEquals('100.00', $result->amount);
        $this->assertNull($result->amount_received);
        $this->assertNull($result->balance);
        $this->assertNull($result->date);
        $this->assertEquals(PaymentStatus::PAID->value,$result->status);
        $this->assertNull($result->reference);
        $this->assertNull($result->url);
        $this->assertNull($result->payment_method_details);
    }

    // ==================== TO PAYMENT DATA RESPONSE TESTS ====================

    #[Test]
    public function to_payment_data_response_creates_response_from_domain_payment(): void
    {
        // Arrange
        $payment = $this->createMock(DomainPayment::class);
        $payment->id = 123;
        $payment->amount = '300.00';
        $payment->amount_received = '300.00';
        $payment->status = PaymentStatus::PAID;
        $payment->payment_intent_id = 'pi_789';

        // Act
        $result = PaymentMapper::toPaymentDataResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentDataResponse::class, $result);
        $this->assertEquals(123, $result->id);
        $this->assertEquals('300.00', $result->amount);
        $this->assertEquals('300.00', $result->amount_received);
        $this->assertEquals('paid', $result->status);
        $this->assertEquals('pi_789', $result->payment_intent_id);
    }

    // ==================== TO PAYMENT VALIDATE RESPONSE TESTS ====================

    #[Test]
    public function to_payment_validate_response_creates_response(): void
    {
        // Arrange
        $student = new UserDataResponse(
            id: 123,
            fullName: 'John Doe',
            email: 'john@example.com',
            curp: 'DOEJ800101HDFXXX01',
            n_control: 'S001234'
        );

        $payment = new PaymentDataResponse(
            id: 456,
            amount: '500.00',
            amount_received: '500.00',
            status: 'paid',
            payment_intent_id: 'pi_123456789'
        );

        Carbon::setTestNow('2024-03-15 09:30:00');

        $metadata = [
            'wasCreated' => true,
        ];

        // Act
        $result = PaymentMapper::toPaymentValidateResponse($student, $payment, $metadata);

        // Assert
        $this->assertInstanceOf(PaymentValidateResponse::class, $result);

        // Check student
        $this->assertInstanceOf(UserDataResponse::class, $result->student);
        $this->assertEquals(123, $result->student->id);
        $this->assertEquals('John Doe', $result->student->fullName);
        $this->assertEquals('john@example.com', $result->student->email);
        $this->assertEquals('DOEJ800101HDFXXX01', $result->student->curp);
        $this->assertEquals('S001234', $result->student->n_control);

        // Check payment
        $this->assertInstanceOf(PaymentDataResponse::class, $result->payment);
        $this->assertEquals(456, $result->payment->id);
        $this->assertEquals('500.00', $result->payment->amount);
        $this->assertEquals('500.00', $result->payment->amount_received);
        $this->assertEquals('paid', $result->payment->status);
        $this->assertEquals('pi_123456789', $result->payment->payment_intent_id);

        // Check timestamp
        $this->assertEquals('2024-03-15 09:30:00', $result->updatedAt);
    }

    #[Test]
    public function to_payment_validate_response_with_null_values(): void
    {
        // Arrange
        $student = new UserDataResponse(
            id: null,
            fullName: null,
            email: null,
            curp: null,
            n_control: null
        );

        $payment = new PaymentDataResponse(
            id: null,
            amount: null,
            amount_received: null,
            status: null,
            payment_intent_id: null
        );

        $result = new ReconciliationResult();

        Carbon::setTestNow('2024-03-15 09:30:00');

        $metadata = [
            'wasReconciled' => true,
            'resultReconciled' => $result->toArray(),
        ];

        // Act
        $result = PaymentMapper::toPaymentValidateResponse($student, $payment, $metadata);

        // Assert
        $this->assertInstanceOf(PaymentValidateResponse::class, $result);
        $this->assertNull($result->student->id);
        $this->assertNull($result->student->fullName);
        $this->assertNull($result->student->email);
        $this->assertNull($result->student->curp);
        $this->assertNull($result->student->n_control);
        $this->assertNull($result->payment->id);
        $this->assertNull($result->payment->amount);
        $this->assertNull($result->payment->amount_received);
        $this->assertNull($result->payment->status);
        $this->assertNull($result->payment->payment_intent_id);
    }

    // ==================== TO LIST ITEM RESPONSE TESTS ====================

    #[Test]
    public function to_list_item_response_creates_response_from_payment(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create([
            'name' => 'John',
            'last_name' => 'Doe'
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'concept_name' => 'Lab Fee',
            'amount' => '75.50',
            'amount_received' => '75.50',
            'created_at' => '2024-03-10 11:20:00',
            'payment_method_details' => ['type' => 'card']
        ]);

        // Act
        $result = PaymentMapper::toListItemResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentListItemResponse::class, $result);
        $this->assertEquals($payment->id, $result->id);
        $this->assertEquals('2024-03-10 11:20:00', $result->date);
        $this->assertEquals('Lab Fee', $result->concept);
        $this->assertEquals('75.50', $result->amount);
        $this->assertEquals('75.50', $result->amount_received);
        $this->assertEquals('card', $result->method);
        $this->assertEquals('John Doe', $result->fullName);
    }

    #[Test]
    public function to_list_item_response_with_unknown_method_type(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create([
            'name' => 'Jane',
            'last_name' => 'Smith'
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'concept_name' => 'Test Fee',
            'amount' => '100.00',
            'amount_received' => '100.00',
            'created_at' => '2024-03-12 14:30:00',
            'payment_method_details' => [] // No type specified
        ]);

        // Act
        $result = PaymentMapper::toListItemResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentListItemResponse::class, $result);
        $this->assertEquals('desconocido', $result->method); // Default value
        $this->assertEquals('Jane Smith', $result->fullName);
    }

    #[Test]
    public function to_list_item_response_with_null_values(): void
    {
        // Arrange
        $user = new \App\Models\User();
        $user->name = null;
        $user->last_name = null;

        $payment = new Payment();
        $payment->id = 999;
        $payment->concept_name = 'Test';
        $payment->amount = '100.00';
        $payment->amount_received = null;
        $payment->created_at = null;
        $payment->payment_method_details = null;
        $payment->user = $user;

        // Act
        $result = PaymentMapper::toListItemResponse($payment);

        // Assert
        $this->assertInstanceOf(PaymentListItemResponse::class, $result);
        $this->assertEquals(999, $result->id);
        $this->assertNull($result->date);
        $this->assertEquals('Test', $result->concept);
        $this->assertEquals('100.00', $result->amount);
        $this->assertNull($result->amount_received);
        $this->assertEquals('desconocido', $result->method);
        $this->assertEquals(' ', $result->fullName); // name + ' ' + last_name = ' '
    }

    // ==================== TO FINANCIAL SUMMARY RESPONSE TESTS ====================

    #[Test]
    public function to_financial_summary_response_creates_response(): void
    {
        // Arrange
        $totalPayments = '10000.50';
        $paymentsBySemester = [
            ['semester' => '2024-1', 'amount' => '5000.25'],
            ['semester' => '2024-2', 'amount' => '5000.25']
        ];
        $totalPayouts = '9500.00';
        $totalFees = '500.50';
        $payoutsBySemester = [
            ['semester' => '2024-1', 'amount' => '4750.00'],
            ['semester' => '2024-2', 'amount' => '4750.00']
        ];
        $totalAvailable = '500.50';
        $totalPending = '2000.00';
        $availableBySource = ['stripe' => '500.50'];
        $pendingBySource = ['stripe' => '2000.00'];
        $availablePercentage = '5.00';
        $pendingPercentage = '20.00';
        $netReceivedPercentage = '95.00';
        $feePercentage = '5.00';

        // Act
        $result = PaymentMapper::toFinancialSummaryResponse(
            $totalPayments, $paymentsBySemester, $totalPayouts,
            $totalFees, $payoutsBySemester, $totalAvailable, $totalPending,
            $availableBySource, $pendingBySource, $availablePercentage,
            $pendingPercentage, $netReceivedPercentage, $feePercentage
        );

        // Assert
        $this->assertInstanceOf(FinancialSummaryResponse::class, $result);
        $this->assertEquals('10000.50', $result->totalPayments);
        $this->assertEquals('9500.00', $result->totalPayouts);
        $this->assertEquals('500.50', $result->totalFees);
        $this->assertEquals($paymentsBySemester, $result->paymentsBySemester);
        $this->assertEquals($payoutsBySemester, $result->payoutsBySemester);
        $this->assertEquals('500.50', $result->totalBalanceAvailable);
        $this->assertEquals('2000.00', $result->totalBalancePending);
        $this->assertEquals('5.00', $result->availablePercentage);
        $this->assertEquals('20.00', $result->pendingPercentage);
        $this->assertEquals('95.00', $result->netReceivedPercentage);
        $this->assertEquals('5.00', $result->feePercentage);
        $this->assertEquals($availableBySource, $result->totalBalanceAvailableBySource);
        $this->assertEquals($pendingBySource, $result->totalBalancePendingBySource);
    }

    // ==================== TO PAYMENTS SUMMARY RESPONSE TESTS ====================

    #[Test]
    public function to_payments_summary_response_creates_response(): void
    {
        // Arrange
        $data = [
            'total' => '15000.75',
            'by_month' => [
                '2024-01' => '5000.25',
                '2024-02' => '10000.50'
            ]
        ];

        // Act
        $result = PaymentMapper::toPaymentsSummaryResponse($data);

        // Assert
        $this->assertInstanceOf(PaymentsSummaryResponse::class, $result);
        $this->assertEquals('15000.75', $result->totalPayments);
        $this->assertEquals($data['by_month'], $result->paymentsByMonth);
    }

    // ==================== TO PAYMENTS MADE BY CONCEPT NAME TESTS ====================

    #[Test]
    public function to_payments_made_by_concept_name_creates_response(): void
    {
        // Arrange - Crear un Payment con las propiedades necesarias
        $payment = new Payment();
        $payment->concept_name = 'Tuition Fee';
        // Asegurarse de que las propiedades existan o usar __set
        $payment->amount_total = '10000.00';
        $payment->amount_received_total = '9500.00';
        $payment->first_payment_date = '2024-01-15 10:30:00';
        $payment->last_payment_date = '2024-03-20 14:45:00';

        // Act
        $result = PaymentMapper::toPaymentsMadeByConceptName($payment);

        // Assert
        $this->assertInstanceOf(PaymentsMadeByConceptName::class, $result);
        $this->assertEquals('Tuition Fee', $result->concept_name);
        $this->assertEquals('10000.00', $result->amount_total);
        $this->assertEquals('9500.00', $result->amount_received_total);
        $this->assertEquals('2024-01-15 10:30:00', $result->first_payment_date);
        $this->assertEquals('2024-03-20 14:45:00', $result->last_payment_date);
        $this->assertEquals('95.00', $result->collection_rate);
    }

    #[Test]
    public function to_payments_made_by_concept_name_with_zero_amount_total(): void
    {
        // Arrange
        $payment = new Payment();
        $payment->concept_name = 'Empty Concept';
        $payment->amount_total = '0.00';
        $payment->amount_received_total = '0.00';
        $payment->first_payment_date = null;
        $payment->last_payment_date = null;

        // Act
        $result = PaymentMapper::toPaymentsMadeByConceptName($payment);

        // Assert
        $this->assertInstanceOf(PaymentsMadeByConceptName::class, $result);
        $this->assertEquals('Empty Concept', $result->concept_name);
        $this->assertEquals('0.00', $result->amount_total);
        $this->assertEquals('0.00', $result->amount_received_total);
        $this->assertEquals('s/f', $result->first_payment_date);
        $this->assertEquals('s/f', $result->last_payment_date);
        $this->assertEquals('0.00', $result->collection_rate);
    }

    #[Test]
    public function to_payments_made_by_concept_name_calculates_collection_rate_correctly(): void
    {
        $testCases = [
            ['total' => '10000.00', 'received' => '10000.00', 'expected' => '100.00'],
            ['total' => '10000.00', 'received' => '7500.00', 'expected' => '75.00'],
            ['total' => '10000.00', 'received' => '5000.00', 'expected' => '50.00'],
            ['total' => '10000.00', 'received' => '2500.00', 'expected' => '25.00'],
            ['total' => '10000.00', 'received' => '0.00', 'expected' => '0.00'],
            ['total' => '500.50', 'received' => '375.25', 'expected' => '74.98'],
        ];

        foreach ($testCases as $case) {
            $payment = new Payment();
            $payment->concept_name = 'Test Concept';
            $payment->amount_total = $case['total'];
            $payment->amount_received_total = $case['received'];
            $payment->first_payment_date = '2024-01-01';
            $payment->last_payment_date = '2024-12-31';

            $result = PaymentMapper::toPaymentsMadeByConceptName($payment);

            $this->assertEquals($case['expected'], $result->collection_rate,
                "Failed for total: {$case['total']}, received: {$case['received']}");
        }
    }
    private function createStripeSession(array $data = []): Session
    {
        $sessionData = array_merge([
            'id' => $data['id'] ?? 'cs_test_123',
            'payment_intent' => $data['payment_intent'] ?? 'pi_123',
            'payment_status_detailed' => $data['payment_status_detailed'] ?? 'paid',
            'payment_status' => $data['payment_status'] ?? 'paid',
            'amount_total' => $data['amount_total'] ?? 15000,
            'amount_received' => $data['amount_received'] ?? 15000,
            'created' => $data['created'] ?? 1672531200,
            'url' => $data['url'] ?? 'https://receipt.stripe.com/test',
            'metadata' => $data['metadata'] ?? ['concept_name' => 'Annual Subscription'],
        ], $data);

        $session = \Stripe\Util\Util::convertToStripeObject($sessionData, ['api_key' => null]);

        $session = Session::constructFrom($sessionData);

        return $session;
    }


}
