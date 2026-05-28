<?php

namespace Tests\Unit\Domain\Validators;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Utils\Validators\PaymentValidator;
use App\Exceptions\NotAllowed\PaymentRetryNotAllowedException;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class PaymentValidatorTest extends TestCase
{
    private function createMockPayment(array $properties = []): MockObject
    {
        $mock = $this->createMock(Payment::class);

        $defaults = [
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => null,
            'created_at' => Carbon::now()->subMinutes(30), // Reciente (menos de 1 hora)
            'id' => 1,
            'concept_name' => 'Test Payment',
            'amount' => '100.00',
            'user_id' => 1,
            'payment_concept_id' => 1,
            'payment_method_id' => null,
            'stripe_payment_method_id' => null,
            'payment_intent_id' => null,
            'url' => null,
            'stripe_session_id' => null,
        ];

        $properties = array_merge($defaults, $properties);

        // Configurar métodos principales
        $mock->method('isRecentPayment')->willReturn(
            $properties['created_at']->gt(Carbon::now()->subHour())
        );

        $mock->method('isNonPaid')->willReturn(
            in_array($properties['status'], PaymentStatus::nonPaidStatuses())
        );

        // Configurar propiedades públicas
        $mock->status = $properties['status'];
        $mock->amount_received = $properties['amount_received'];
        $mock->created_at = $properties['created_at'];
        $mock->id = $properties['id'];
        $mock->concept_name = $properties['concept_name'];
        $mock->amount = $properties['amount'];
        $mock->user_id = $properties['user_id'];
        $mock->payment_concept_id = $properties['payment_concept_id'];

        return $mock;
    }

    // Tests para ensurePaymentIsValidToRepay
    #[Test]
    public function ensurePaymentIsValidToRepay_passes_when_all_conditions_met(): void
    {
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => null,
            'created_at' => Carbon::now()->subMinutes(30), // Reciente
        ]);

        $this->expectNotToPerformAssertions();
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_throws_when_payment_not_recent(): void
    {
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => null,
            'created_at' => Carbon::now()->subHours(2), // No reciente (más de 1 hora)
        ]);

        $this->expectException(PaymentRetryNotAllowedException::class);
        $this->expectExceptionMessage('No se puede volver a pagar: el intento de pago anterior fue hace más de 1 hora.');
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_throws_when_amount_received_not_null(): void
    {
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => '50.00', // Ya recibió algún monto
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->expectException(PaymentRetryNotAllowedException::class);
        $this->expectExceptionMessage('No se puede volver a pagar: el pago ya recibió algún monto.');
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_throws_when_payment_not_nonpaid(): void
    {
        // Probar con diferentes estados terminales
        $terminalStatuses = [
            PaymentStatus::PAID,
            PaymentStatus::SUCCEEDED,
            PaymentStatus::OVERPAID,
        ];

        foreach ($terminalStatuses as $status) {
            $payment = $this->createMockPayment([
                'status' => $status,
                'amount_received' => null,
                'created_at' => Carbon::now()->subMinutes(30),
            ]);

            try {
                PaymentValidator::ensurePaymentIsValidToRepay($payment);
                $this->fail("Expected exception for status: {$status->value}");
            } catch (PaymentRetryNotAllowedException $e) {
                $this->assertStringContainsString('No se puede volver a pagar: el pago actual ya está en estado terminal.', $e->getMessage());
            }
        }
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_passes_for_all_nonpaid_statuses(): void
    {
        // Obtener todos los estados no pagados
        $nonPaidStatuses = PaymentStatus::nonPaidStatuses();

        foreach ($nonPaidStatuses as $status) {
            $payment = $this->createMockPayment([
                'status' => $status,
                'amount_received' => null,
                'created_at' => Carbon::now()->subMinutes(30),
            ]);

            $this->expectNotToPerformAssertions();
            PaymentValidator::ensurePaymentIsValidToRepay($payment);
        }
    }

    // Tests edge cases
    #[Test]
    public function ensurePaymentIsValidToRepay_throws_when_just_at_one_hour_limit(): void
    {
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => null,
            'created_at' => Carbon::now()->subHour(), // Exactamente 1 hora
        ]);

        // Carbon::gt() es estricto (>), no >=
        // created_at = now - 1h, now - 1h = now - 1h, no es mayor, es igual
        // Por lo tanto isRecentPayment() debería retornar false
        $this->expectException(PaymentRetryNotAllowedException::class);
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_passes_when_just_under_one_hour(): void
    {
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => null,
            'created_at' => Carbon::now()->subHour()->addMinute(), // 59 minutos
        ]);

        $this->expectNotToPerformAssertions();
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_throws_when_amount_received_is_zero_string(): void
    {
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => '0.00', // Aunque sea 0, ya tiene un valor
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->expectException(PaymentRetryNotAllowedException::class);
        $this->expectExceptionMessage('No se puede volver a pagar: el pago ya recibió algún monto.');
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_throws_when_amount_received_is_empty_string(): void
    {
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => '', // String vacío no es null
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->expectException(PaymentRetryNotAllowedException::class);
        $this->expectExceptionMessage('No se puede volver a pagar: el pago ya recibió algún monto.');
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    // Tests para verificar que el orden de validaciones es correcto
    #[Test]
    public function ensurePaymentIsValidToRepay_checks_recency_first(): void
    {
        // Si no es reciente, debe fallar incluso si otros campos son válidos
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => null,
            'created_at' => Carbon::now()->subHours(2), // No reciente
        ]);

        $this->expectException(PaymentRetryNotAllowedException::class);
        $this->expectExceptionMessage('No se puede volver a pagar: el intento de pago anterior fue hace más de 1 hora.');
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_checks_amount_received_second(): void
    {
        // Si es reciente pero tiene amount_received, debe fallar
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::DEFAULT,
            'amount_received' => '100.00', // Tiene monto recibido
            'created_at' => Carbon::now()->subMinutes(30), // Reciente
        ]);

        $this->expectException(PaymentRetryNotAllowedException::class);
        $this->expectExceptionMessage('No se puede volver a pagar: el pago ya recibió algún monto.');
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_checks_status_last(): void
    {
        // Si es reciente y no tiene amount_received pero tiene estado terminal
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::PAID, // Estado terminal
            'amount_received' => null,
            'created_at' => Carbon::now()->subMinutes(30), // Reciente
        ]);

        $this->expectException(PaymentRetryNotAllowedException::class);
        $this->expectExceptionMessage('No se puede volver a pagar: el pago actual ya está en estado terminal.');
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    // Test adicional para verificar que UNDERPAID y OVERPAID son estados terminales
    #[Test]
    public function ensurePaymentIsValidToRepay_throws_for_underpaid_status(): void
    {
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::UNDERPAID,
            'amount_received' => null,
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->expectException(PaymentRetryNotAllowedException::class);
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }

    #[Test]
    public function ensurePaymentIsValidToRepay_throws_for_overpaid_status(): void
    {
        $payment = $this->createMockPayment([
            'status' => PaymentStatus::OVERPAID,
            'amount_received' => null,
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->expectException(PaymentRetryNotAllowedException::class);
        PaymentValidator::ensurePaymentIsValidToRepay($payment);
    }
}
