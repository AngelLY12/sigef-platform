<?php

namespace Tests\Unit\Domain\Entities;

use App\Core\Domain\Enum\Payment\PaymentStatus;
use Carbon\Carbon;
use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\Payment;
use PHPUnit\Framework\Attributes\Test;

class PaymentTest extends BaseDomainTestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $payment = new Payment(
            concept_name: 'Colegiatura',
            amount: '1500.00',
            status: PaymentStatus::DEFAULT
        );

        $this->assertInstanceOf(Payment::class, $payment);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_parameters()
    {
        $createdAt = Carbon::now()->subDay();

        $payment = new Payment(
            concept_name: 'Inscripción Semestral',
            amount: '2500.50',
            status: PaymentStatus::SUCCEEDED,
            payment_method_details: ['brand' => 'visa', 'last4' => '4242'],
            id: 100,
            user_id: 50,
            payment_concept_id: 10,
            payment_method_id: 5,
            stripe_payment_method_id: 'pm_123456789',
            amount_received: '2500.50',
            payment_intent_id: 'pi_abcdefghij',
            url: 'https://checkout.stripe.com/pay/test',
            stripe_session_id: 'cs_test_123',
            created_at: $createdAt
        );

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('Inscripción Semestral', $payment->concept_name);
        $this->assertEquals('2500.50', $payment->amount);
        $this->assertEquals(['brand' => 'visa', 'last4' => '4242'], $payment->payment_method_details);
        $this->assertEquals(PaymentStatus::SUCCEEDED, $payment->status);
        $this->assertEquals(100, $payment->id);
        $this->assertEquals(50, $payment->user_id);
        $this->assertEquals(10, $payment->payment_concept_id);
        $this->assertEquals(5, $payment->payment_method_id);
        $this->assertEquals('pm_123456789', $payment->stripe_payment_method_id);
        $this->assertEquals('2500.50', $payment->amount_received);
        $this->assertEquals('pi_abcdefghij', $payment->payment_intent_id);
        $this->assertEquals('https://checkout.stripe.com/pay/test', $payment->url);
        $this->assertEquals('cs_test_123', $payment->stripe_session_id);
        $this->assertEquals($createdAt, $payment->created_at);
    }

    #[Test]
    public function it_has_required_attributes()
    {
        $payment = new Payment(
            concept_name: 'Mensualidad',
            amount: '500.00',
            status: PaymentStatus::DEFAULT,
        );

        $this->assertEquals('Mensualidad', $payment->concept_name);
        $this->assertEquals('500.00', $payment->amount);
        $this->assertEquals(PaymentStatus::DEFAULT, $payment->status);
        $this->assertNull($payment->id);
        $this->assertNull($payment->user_id);
        $this->assertNull($payment->payment_concept_id);
        $this->assertNull($payment->payment_method_id);
        $this->assertNull($payment->stripe_payment_method_id);
        $this->assertNull($payment->amount_received);
        $this->assertNull($payment->payment_intent_id);
        $this->assertNull($payment->url);
        $this->assertNull($payment->stripe_session_id);
        $this->assertNull($payment->created_at);
        $this->assertEquals([], $payment->payment_method_details);
    }

    #[Test]
    public function it_accepts_valid_data()
    {
        $payment = new Payment(
            concept_name: 'Examen de Admisión',
            amount: '800.00',
            status: PaymentStatus::REQUIRES_ACTION,
            payment_method_details: ['method' => 'card'],
            id: 25,
            user_id: 30,
            created_at: Carbon::now()
        );

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('Examen de Admisión', $payment->concept_name);
        $this->assertEquals('800.00', $payment->amount);
        $this->assertEquals(['method' => 'card'], $payment->payment_method_details);
        $this->assertEquals(PaymentStatus::REQUIRES_ACTION, $payment->status);
        $this->assertEquals(25, $payment->id);
        $this->assertEquals(30, $payment->user_id);
        $this->assertInstanceOf(Carbon::class, $payment->created_at);
    }

    #[Test]
    public function it_calculates_pending_amount_correctly()
    {
        $payment1 = new Payment(
            concept_name: 'Test 1',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT
        );
        $this->assertEquals('1000.00', $payment1->getPendingAmount());

        $payment2 = new Payment(
            concept_name: 'Test 2',
            amount: '1000.00',
            status: PaymentStatus::UNDERPAID,
            amount_received: '500.00'
        );
        $this->assertEquals('500.00', $payment2->getPendingAmount());

        $payment3 = new Payment(
            concept_name: 'Test 3',
            amount: '1000.00',
            status: PaymentStatus::SUCCEEDED,
            amount_received: '1000.00'
        );
        $this->assertEquals('0.00', $payment3->getPendingAmount());

        $payment4 = new Payment(
            concept_name: 'Test 4',
            amount: '1000.00',
            status: PaymentStatus::OVERPAID,
            amount_received: '1200.00'
        );
        $this->assertEquals('0.00', $payment4->getPendingAmount());

        $payment5 = new Payment(
            concept_name: 'Test 5',
            amount: '750.50',
            status: PaymentStatus::DEFAULT,
            amount_received: null
        );
        $this->assertEquals('750.50', $payment5->getPendingAmount());

        $payment6 = new Payment(
            concept_name: 'Test 6',
            amount: '600.25',
            status: PaymentStatus::DEFAULT,
            amount_received: ''
        );
        $this->assertEquals('600.25', $payment6->getPendingAmount());
    }

    #[Test]
    public function it_calculates_overpaid_amount_correctly()
    {
        $payment1 = new Payment(
            concept_name: 'Test 1',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT
        );
        $this->assertEquals('0.00', $payment1->getOverPaidAmount());

        $payment2 = new Payment(
            concept_name: 'Test 2',
            amount: '1000.00',
            status: PaymentStatus::UNDERPAID,
            amount_received: '500.00'
        );
        $this->assertEquals('0.00', $payment2->getOverPaidAmount());

        $payment3 = new Payment(
            concept_name: 'Test 3',
            amount: '1000.00',
            status: PaymentStatus::SUCCEEDED,
            amount_received: '1000.00'
        );
        $this->assertEquals('0.00', $payment3->getOverPaidAmount());

        $payment4 = new Payment(
            concept_name: 'Test 4',
            amount: '1000.00',
            status: PaymentStatus::OVERPAID,
            amount_received: '1200.00'
        );
        $this->assertEquals('200.00', $payment4->getOverPaidAmount());

        $payment5 = new Payment(
            concept_name: 'Test 5',
            amount: '999.99',
            status: PaymentStatus::OVERPAID,
            amount_received: '1000.00'
        );
        $this->assertEquals('0.01', $payment5->getOverPaidAmount());

        $payment6 = new Payment(
            concept_name: 'Test 6',
            amount: '500.00',
            status: PaymentStatus::DEFAULT,
            amount_received: null
        );
        $this->assertEquals('0.00', $payment6->getOverPaidAmount());
    }

    #[Test]
    public function it_detects_overpaid_status()
    {
        $overpaidPayment = new Payment(
            concept_name: 'Overpaid',
            amount: '1000.00',
            status: PaymentStatus::OVERPAID
        );
        $this->assertTrue($overpaidPayment->isOverPaid());

        $otherPayment = new Payment(
            concept_name: 'Other',
            amount: '1000.00',
            status: PaymentStatus::SUCCEEDED
        );
        $this->assertFalse($otherPayment->isOverPaid());

        $pendingPayment = new Payment(
            concept_name: 'Pending',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT
        );
        $this->assertFalse($pendingPayment->isOverPaid());
    }

    #[Test]
    public function it_detects_underpaid_status()
    {
        $underpaidPayment = new Payment(
            concept_name: 'Underpaid',
            amount: '1000.00',
            status: PaymentStatus::UNDERPAID
        );
        $this->assertTrue($underpaidPayment->isUnderPaid());

        $otherPayment = new Payment(
            concept_name: 'Other',
            amount: '1000.00',
            status: PaymentStatus::SUCCEEDED
        );
        $this->assertFalse($otherPayment->isUnderPaid());

        $pendingPayment = new Payment(
            concept_name: 'Pending',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT
        );
        $this->assertFalse($pendingPayment->isUnderPaid());
    }

    #[Test]
    public function it_detects_non_paid_statuses()
    {
        $nonPaidStatuses = PaymentStatus::nonPaidStatuses();

        foreach ($nonPaidStatuses as $status) {
            $payment = new Payment(
                concept_name: 'Test ' . $status->value,
                amount: '1000.00',
                status: $status
            );

            $this->assertTrue($payment->isNonPaid(), "Status {$status->value} debería ser considerado no pagado");        }

        $paidStatuses = [
            PaymentStatus::SUCCEEDED,
            PaymentStatus::OVERPAID,
            PaymentStatus::PAID
        ];

        foreach ($paidStatuses as $status) {
            $payment = new Payment(
                concept_name: 'Test ' . $status->value,
                amount: '1000.00',
                status: $status
            );
            $this->assertFalse($payment->isNonPaid(), "Status {$status->value} NO debería ser considerado no pagado");
        }
    }

    #[Test]
    public function it_detects_recent_payments()
    {
        $recentPayment = new Payment(
            concept_name: 'Recent',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT,
            created_at: Carbon::now()->subMinutes(30)
        );
        $this->assertTrue($recentPayment->isRecentPayment());

        $oldPayment = new Payment(
            concept_name: 'Old',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT,
            created_at: Carbon::now()->subHours(2)
        );
        $this->assertFalse($oldPayment->isRecentPayment());

        $noDatePayment = new Payment(
            concept_name: 'No Date',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT
        );

        $this->assertFalse($noDatePayment->isRecentPayment());
    }

    #[Test]
    public function it_handles_decimal_amounts_correctly()
    {
        $testCases = [
            ['amount' => '1000.00', 'received' => '500.50', 'pending' => '499.50', 'overpaid' => '0.00'],
            ['amount' => '999.99', 'received' => '999.99', 'pending' => '0.00', 'overpaid' => '0.00'],
            ['amount' => '100.00', 'received' => '100.01', 'pending' => '0.00', 'overpaid' => '0.01'],
            ['amount' => '0.01', 'received' => '0.00', 'pending' => '0.01', 'overpaid' => '0.00'],
            ['amount' => '0.00', 'received' => '0.00', 'pending' => '0.00', 'overpaid' => '0.00'],
            ['amount' => '1234.56', 'received' => '1234.56', 'pending' => '0.00', 'overpaid' => '0.00'],
        ];

        foreach ($testCases as $test) {
            $payment = new Payment(
                concept_name: 'Test',
                amount: $test['amount'],
                status: PaymentStatus::UNDERPAID,
                amount_received: $test['received']
            );

            $this->assertEquals($test['pending'], $payment->getPendingAmount(),
                "Pending amount incorrecto para {$test['amount']} - {$test['received']}");
            $this->assertEquals($test['overpaid'], $payment->getOverPaidAmount(),
                "Overpaid amount incorrecto para {$test['amount']} - {$test['received']}");
        }
    }

    #[Test]
    public function it_handles_edge_cases_for_amounts()
    {
        $largeAmount = new Payment(
            concept_name: 'Large',
            amount: '9999999999.99',
            status: PaymentStatus::DEFAULT,
            amount_received: '5000000000.00'
        );
        $this->assertEquals('4999999999.99', $largeAmount->getPendingAmount());

        $decimalAmount = new Payment(
            concept_name: 'Decimal',
            amount: '100.999',
            status: PaymentStatus::DEFAULT,
            amount_received: '50.555'
        );
        $this->assertEquals('50.44', $decimalAmount->getPendingAmount()); // 100.99 - 50.55

    }

    #[Test]
    public function it_accepts_different_payment_method_details()
    {
        $details = [
            [],
            ['brand' => 'visa', 'last4' => '4242'],
            ['type' => 'card', 'brand' => 'mastercard', 'exp_month' => '12', 'exp_year' => '2025'],
            ['method' => 'bank_transfer', 'bank_name' => 'BBVA'],
            ['custom_data' => ['key' => 'value']],
        ];

        foreach ($details as $detail) {
            $payment = new Payment(
                concept_name: 'Test',
                amount: '100.00',
                status: PaymentStatus::DEFAULT,
                payment_method_details: $detail
            );

            $this->assertEquals($detail, $payment->payment_method_details);
        }
    }

    #[Test]
    public function it_can_be_converted_to_json()
    {
        $payment = new Payment(
            concept_name: 'Deportes',
            amount: '200.00',
            status: PaymentStatus::REQUIRES_ACTION,
            payment_method_details: ['type' => 'card'],
            id: 75,
            user_id: 80,
            created_at: Carbon::now()
        );

        $json = json_encode($payment);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(75, $decoded['id']);
        $this->assertEquals('Deportes', $decoded['concept_name']);
        $this->assertEquals('200.00', $decoded['amount']);
        $this->assertEquals(['type' => 'card'], $decoded['payment_method_details']);
        $this->assertEquals(PaymentStatus::REQUIRES_ACTION->value, $decoded['status']);
        $this->assertEquals(80, $decoded['user_id']);
        $this->assertNull($decoded['payment_concept_id']);
        $this->assertNull($decoded['payment_method_id']);
        $this->assertNull($decoded['stripe_payment_method_id']);
        $this->assertNull($decoded['amount_received']);
        $this->assertNull($decoded['payment_intent_id']);
        $this->assertNull($decoded['url']);
        $this->assertNull($decoded['stripe_session_id']);
    }

    #[Test]
    public function it_handles_all_payment_statuses()
    {
        $statuses = PaymentStatus::cases();

        foreach ($statuses as $status) {
            $payment = new Payment(
                concept_name: 'Test ' . $status->value,
                amount: '1000.00',
                status: $status,
                amount_received: '500.00'
            );

            $this->assertEquals($status, $payment->status);

            $this->assertIsString($payment->getPendingAmount());
            $this->assertIsString($payment->getOverPaidAmount());
            $this->assertIsBool($payment->isOverPaid());
            $this->assertIsBool($payment->isUnderPaid());
            $this->assertIsBool($payment->isNonPaid());
        }
    }

    #[Test]
    public function it_handles_stripe_related_fields()
    {
        $testCases = [
            [
                'stripe_payment_method_id' => 'pm_123',
                'payment_intent_id' => 'pi_456',
                'stripe_session_id' => 'cs_789',
                'url' => 'https://checkout.stripe.com/test'
            ],
            [
                'stripe_payment_method_id' => null,
                'payment_intent_id' => null,
                'stripe_session_id' => null,
                'url' => null
            ],
            [
                'stripe_payment_method_id' => 'pm_test_' . str_repeat('a', 50),
                'payment_intent_id' => 'pi_test_' . str_repeat('b', 50),
                'stripe_session_id' => 'cs_test_' . str_repeat('c', 50),
                'url' => 'https://checkout.stripe.com/pay/' . str_repeat('d', 100)
            ]
        ];

        foreach ($testCases as $case) {
            $payment = new Payment(
                concept_name: 'Stripe Test',
                amount: '100.00',
                status: PaymentStatus::DEFAULT,
                stripe_payment_method_id: $case['stripe_payment_method_id'],
                payment_intent_id: $case['payment_intent_id'],
                url: $case['url'],
                stripe_session_id: $case['stripe_session_id']
            );

            $this->assertEquals($case['stripe_payment_method_id'], $payment->stripe_payment_method_id);
            $this->assertEquals($case['payment_intent_id'], $payment->payment_intent_id);
            $this->assertEquals($case['stripe_session_id'], $payment->stripe_session_id);
            $this->assertEquals($case['url'], $payment->url);
        }
    }

    #[Test]
    public function it_calculates_amounts_with_various_null_scenarios()
    {
        $nullReceived = new Payment(
            concept_name: 'Null Received',
            amount: '1000.00',
            status: PaymentStatus::DEFAULT,
            amount_received: null
        );
        $this->assertEquals('1000.00', $nullReceived->getPendingAmount());
        $this->assertEquals('0.00', $nullReceived->getOverPaidAmount());
    }

    #[Test]
    public function it_provides_correct_financial_summary()
    {
        $payment = new Payment(
            concept_name: 'Test Summary',
            amount: '1500.00',
            status: PaymentStatus::UNDERPAID,
            amount_received: '800.00'
        );

        $pending = $payment->getPendingAmount();
        $overpaid = $payment->getOverPaidAmount();
        $isOverpaid = $payment->isOverPaid();
        $isUnderpaid = $payment->isUnderPaid();
        $isNonPaid = $payment->isNonPaid();

        $this->assertEquals('700.00', $pending);
        $this->assertEquals('0.00', $overpaid);
        $this->assertFalse($isOverpaid);
        $this->assertTrue($isUnderpaid);
        $this->assertFalse($isNonPaid);

        $payment->status = PaymentStatus::OVERPAID;
        $payment->amount_received = '1600.00';

        $this->assertEquals('0.00', $payment->getPendingAmount());
        $this->assertEquals('100.00', $payment->getOverPaidAmount());
        $this->assertTrue($payment->isOverPaid());
        $this->assertFalse($payment->isNonPaid());
    }
}
