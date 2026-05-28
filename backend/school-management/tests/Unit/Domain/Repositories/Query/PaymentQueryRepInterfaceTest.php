<?php

namespace Tests\Unit\Domain\Repositories\Query;
use Tests\Stubs\Repositories\Query\PaymentQueryRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Application\DTO\Response\Payment\PaymentHistoryResponse;
use App\Core\Application\DTO\Response\Payment\PaymentListItemResponse;
use App\Core\Application\DTO\Response\Payment\PaymentDetailResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Generator;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class PaymentQueryRepInterfaceTest extends BaseRepositoryTestCase
{
    protected string $interfaceClass = PaymentQueryRepInterface::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PaymentQueryRepStub();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository);
        $this->assertImplementsInterface($this->interfaceClass);
    }

    #[Test]
    public function it_has_all_required_methods(): void
    {
        $methods = [
            'findBySessionId',
            'findById',
            'findByIds',
            'findByIntentId',
            'sumPaymentsByUserYear',
            'getPaymentHistory',
            'getAllPaymentsMade',
            'getPaymentHistoryWithDetails',
            'findByIntentOrSession',
            'getReconciliablePaymentsCursor',
            'getAllWithSearchEager',
            'getLastPaymentForConcept'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function findBySessionId_returns_payment_when_found(): void
    {
        $payment = new Payment(
            concept_name: 'Inscripción',
            amount: '1500.00',
            status: PaymentStatus::PAID,
            id: 1,
            user_id: 1,
            amount_received: '1500.00',
            stripe_session_id: 'cs_test_123',
            created_at: Carbon::now()
        );

        $this->repository->setNextFindBySessionIdResult($payment);

        $result = $this->repository->findBySessionId('cs_test_123');

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals('cs_test_123', $result->stripe_session_id);
        $this->assertEquals('Inscripción', $result->concept_name);
        $this->assertTrue($result->isRecentPayment());
    }

    #[Test]
    public function findBySessionId_returns_null_when_not_found(): void
    {
        $this->repository->setNextFindBySessionIdResult(null);

        $result = $this->repository->findBySessionId('invalid_session');

        $this->assertNull($result);
    }

    #[Test]
    public function findById_returns_payment_when_found(): void
    {
        $payment = new Payment(
            concept_name: 'Colegiatura',
            amount: '2500.00',
            status: PaymentStatus::SUCCEEDED,
            id: 2,
            user_id: 1,
            amount_received: '2500.00',
            payment_intent_id: 'pi_123',
            created_at: Carbon::now()->subDay()
        );

        $this->repository->setNextFindByIdResult($payment);

        $result = $this->repository->findById(2);

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('Colegiatura', $result->concept_name);
        $this->assertEquals('0.00', $result->getPendingAmount());
    }

    #[Test]
    public function findByIntentId_returns_payment_when_found(): void
    {
        $payment = new Payment(
            concept_name: 'Materiales',
            amount: '500.00',
            status: PaymentStatus::DEFAULT,
            id: 3,
            user_id: 1,
            payment_intent_id: 'pi_abc123',
            created_at: Carbon::now()
        );

        $this->repository->setNextFindByIntentIdResult($payment);

        $result = $this->repository->findByIntentId('pi_abc123');

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals('pi_abc123', $result->payment_intent_id);
        $this->assertTrue($result->isNonPaid());
    }

    #[Test]
    public function sumPaymentsByUserYear_returns_monthly_aggregation(): void
    {
        $aggregation = [
            'total' => '4500.00',
            'by_month' => [
                '2025-01' => '1500.00',
                '2025-02' => '3000.00',
            ]
        ];

        $this->repository->setNextSumPaymentsByUserYearResult($aggregation);

        $result = $this->repository->sumPaymentsByUserYear(1, true);

        $this->assertIsArray($result);
        $this->assertEquals('4500.00', $result['total']);
        $this->assertArrayHasKey('by_month', $result);
        $this->assertCount(2, $result['by_month']);
        $this->assertEquals('1500.00', $result['by_month']['2025-01']);
    }

    #[Test]
    public function getPaymentHistory_returns_paginated_history(): void
    {
        $items = [
            new PaymentHistoryResponse(1, 'Inscripción', '1500.00', '1500.00', 'paid', '2025-01-15'),
            new PaymentHistoryResponse(2, 'Colegiatura', '2500.00', '2500.00', 'paid', '2025-02-01'),
        ];

        $paginator = new LengthAwarePaginator($items, 10, 5, 1);
        $this->repository->setNextGetPaymentHistoryResult($paginator);

        $result = $this->repository->getPaymentHistory(1, 5, 1, true);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
        $this->assertEquals(10, $result->total());
        $this->assertEquals('Inscripción', $result->items()[0]->concept);
    }

    #[Test]
    public function getAllPaymentsMade_returns_aggregation(): void
    {
        $aggregation = [
            'total' => '15000.00',
            'by_month' => [
                '2025-01' => '5000.00',
                '2025-02' => '10000.00',
            ]
        ];

        $this->repository->setNextGetAllPaymentsMadeResult($aggregation);

        $result = $this->repository->getAllPaymentsMade(true);

        $this->assertIsArray($result);
        $this->assertEquals('15000.00', $result['total']);
        $this->assertCount(2, $result['by_month']);
    }

    #[Test]
    public function getPaymentHistoryWithDetails_returns_paginated_details(): void
    {
        $items = [
            new PaymentDetailResponse(
                1,
                'Inscripción',
                '1500.00',
                '1500.00',
                '0.00',
                '2025-01-15',
                'paid',
                'REF123',
                'https://receipt.url/1',
                ['Tarjeta de crédito', 'Visa']
            ),
        ];

        $paginator = new LengthAwarePaginator($items, 5, 10, 1);
        $this->repository->setNextGetPaymentHistoryWithDetailsResult($paginator);

        $result = $this->repository->getPaymentHistoryWithDetails(1, 10, 1);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(1, $result->items());
        $this->assertInstanceOf(PaymentDetailResponse::class, $result->items()[0]);
        $this->assertEquals('Inscripción', $result->items()[0]->concept);
    }

    #[Test]
    public function findByIntentOrSession_returns_payment_when_found(): void
    {
        $payment = new Payment(
            concept_name: 'Materiales',
            amount: '300.00',
            status: PaymentStatus::PAID,
            id: 4,
            user_id: 1,
            amount_received: '300.00',
            payment_intent_id: 'pi_intent123',
            stripe_session_id: 'cs_session123',
            created_at: Carbon::now()
        );

        $this->repository->setNextFindByIntentOrSessionResult($payment);

        $result = $this->repository->findByIntentOrSession(1, 'pi_intent123');

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(1, $result->user_id);
        $this->assertEquals('pi_intent123', $result->payment_intent_id);
    }

    #[Test]
    public function getPaidWithinLastMonthCursor_returns_generator(): void
    {
        $payment1 = new Payment(
            concept_name: 'Pago 1',
            amount: '1000.00',
            status: PaymentStatus::PAID,
            id: 1,
            user_id: 1,
            amount_received: '1000.00',
            created_at: Carbon::now()->subDays(15)
        );

        $payment2 = new Payment(
            concept_name: 'Pago 2',
            amount: '2000.00',
            status: PaymentStatus::PAID,
            id: 2,
            user_id: 2,
            amount_received: '2000.00',
            created_at: Carbon::now()->subDays(10)
        );

        $generator = (function () use ($payment1, $payment2) {
            yield $payment1;
            yield $payment2;
        })();

        $this->repository->setNextGetPaidWithinLastMonthCursorResult($generator);

        $result = $this->repository->getReconciliablePaymentsCursor();

        $this->assertInstanceOf(Generator::class, $result);

        $payments = iterator_to_array($result);
        $this->assertCount(2, $payments);
        $this->assertContainsOnlyInstancesOf(Payment::class, $payments);
        $this->assertEquals('Pago 1', $payments[0]->concept_name);
    }

    #[Test]
    public function getAllWithSearchEager_returns_paginated_list(): void
    {
        $items = [
            new PaymentListItemResponse(
                1,
                '2025-01-15',
                'Inscripción',
                '1500.00',
                '1500.00',
                'Tarjeta de crédito',
                'Juan Pérez'
            ),
        ];

        $paginator = new LengthAwarePaginator($items, 20, 15, 1);
        $this->repository->setNextGetAllWithSearchEagerResult($paginator);

        $result = $this->repository->getAllWithSearchEager('Juan', 15, 1);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(1, $result->items());
        $this->assertInstanceOf(PaymentListItemResponse::class, $result->items()[0]);
        $this->assertEquals('Juan Pérez', $result->items()[0]->fullName);
    }

    #[Test]
    public function getLastPaymentForConcept_returns_payment_when_found(): void
    {
        $payment = new Payment(
            concept_name: 'Inscripción',
            amount: '1500.00',
            status: PaymentStatus::PAID,
            id: 5,
            user_id: 1,
            payment_concept_id: 1,
            amount_received: '1500.00',
            created_at: Carbon::now()->subDays(5)
        );

        $this->repository->setNextGetLastPaymentForConceptResult($payment);

        $result = $this->repository->getLastPaymentForConcept(1, 1, [PaymentStatus::PAID->value]);

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(1, $result->user_id);
        $this->assertEquals(1, $result->payment_concept_id);
        $this->assertEquals('1500.00', $result->amount_received);
    }

    #[Test]
    public function payment_entity_methods_work_correctly(): void
    {
        $payment = new Payment(
            concept_name: 'Test Payment',
            amount: '1000.00',
            status: PaymentStatus::UNDERPAID,
            payment_method_details: ['Tarjeta de crédito', 'Visa'],
            id: 1,
            user_id: 1,
            amount_received: '800.00',
            created_at: Carbon::now()
        );

        // Test entity methods
        $this->assertEquals('200.00', $payment->getPendingAmount());
        $this->assertEquals('0.00', $payment->getOverPaidAmount());
        $this->assertTrue($payment->isUnderPaid());
        $this->assertFalse($payment->isOverPaid());
        $this->assertFalse($payment->isNonPaid());
        $this->assertTrue($payment->isRecentPayment());

        // Test overpaid scenario
        $overpaidPayment = new Payment(
            concept_name: 'Overpaid',
            amount: '1000.00',
            status: PaymentStatus::OVERPAID,
            id: 2,
            user_id: 1,
            amount_received: '1200.00',
            created_at: Carbon::now()->subHours(2)
        );

        $this->assertEquals('0.00', $overpaidPayment->getPendingAmount());
        $this->assertEquals('200.00', $overpaidPayment->getOverPaidAmount());
        $this->assertTrue($overpaidPayment->isOverPaid());

        // Test unpaid scenario
        $unpaidPayment = new Payment(
            concept_name: 'Unpaid',
            amount: '1000.00',
            status: PaymentStatus::UNPAID,
            id: 3,
            user_id: 1,
            amount_received: null,
            created_at: Carbon::now()
        );

        $this->assertEquals('1000.00', $unpaidPayment->getPendingAmount());
        $this->assertEquals('0.00', $unpaidPayment->getOverPaidAmount());
        $this->assertTrue($unpaidPayment->isNonPaid());
    }

    #[Test]
    public function methods_have_correct_signatures(): void
    {
        $this->assertMethodParameterType('findBySessionId', 'string');
        $this->assertMethodParameterType('findById', 'int');
        $this->assertMethodParameterType('findByIntentId', 'string');
        $this->assertMethodParameterType('sumPaymentsByUserYear', 'int');
        $this->assertMethodParameterType('sumPaymentsByUserYear', 'bool', 1);
        $this->assertMethodParameterCount('getPaymentHistory', 4);
        $this->assertMethodParameterCount('getLastPaymentForConcept', 3);

        $this->assertMethodReturnType('findBySessionId', Payment::class);
        $this->assertMethodReturnType('findById', Payment::class);
        $this->assertMethodReturnType('findByIntentId', Payment::class);
        $this->assertMethodReturnType('sumPaymentsByUserYear', 'array');
        $this->assertMethodReturnType('getPaymentHistory', LengthAwarePaginator::class);
        $this->assertMethodReturnType('getAllPaymentsMade', 'array');
        $this->assertMethodReturnType('getReconciliablePaymentsCursor', Generator::class);
    }

    #[Test]
    public function payment_status_enum_methods_work_correctly(): void
    {
        // Test terminal statuses
        $terminalStatuses = PaymentStatus::paidStatuses();
        $this->assertIsArray($terminalStatuses);
        $this->assertContains(PaymentStatus::SUCCEEDED->value, $terminalStatuses);
        $this->assertContains(PaymentStatus::PAID->value, $terminalStatuses);
        $this->assertContains(PaymentStatus::OVERPAID->value, $terminalStatuses);

        // Test non-paid statuses
        $nonPaidStatuses = PaymentStatus::nonPaidStatuses();
        $this->assertIsArray($nonPaidStatuses);
        $this->assertContains(PaymentStatus::DEFAULT, $nonPaidStatuses);
        $this->assertContains(PaymentStatus::UNPAID, $nonPaidStatuses);
        $this->assertContains(PaymentStatus::REQUIRES_ACTION, $nonPaidStatuses);

        // Test reconcilable statuses
        $reconcilableStatuses = PaymentStatus::reconcilableStatuses();
        $this->assertIsArray($reconcilableStatuses);
        $this->assertContains(PaymentStatus::DEFAULT, $reconcilableStatuses);
        $this->assertContains(PaymentStatus::PAID, $reconcilableStatuses);
    }

    #[Test]
    public function empty_results_return_correct_types(): void
    {
        $this->repository->setNextSumPaymentsByUserYearResult([]);
        $this->repository->setNextGetAllPaymentsMadeResult([]);

        $result1 = $this->repository->sumPaymentsByUserYear(999, false);
        $result2 = $this->repository->getAllPaymentsMade(false);

        $this->assertIsArray($result1);
        $this->assertEmpty($result1);

        $this->assertIsArray($result2);
        $this->assertEmpty($result2);
    }
}
