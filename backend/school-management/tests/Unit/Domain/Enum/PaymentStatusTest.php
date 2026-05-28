<?php

namespace Tests\Unit\Domain\Enum;

use App\Core\Domain\Enum\Payment\PaymentStatus;

use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Domain\EnumTestCase;

class PaymentStatusTest extends EnumTestCase
{
    protected function enumClass(): string
    {
        return PaymentStatus::class;
    }

    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = $this->getAllCases();

        $this->assertCount(8, $cases, 'PaymentStatus debe tener 7 casos');

        $expectedValues = [
            'succeeded',
            'requires_action',
            'paid',
            'unpaid',
            'pending',
            'overpaid',
            'underpaid',
            'failed'
        ];

        foreach ($expectedValues as $expectedValue) {
            $this->assertContains(
                $expectedValue,
                $this->getAllValues(),
                "PaymentStatus debe incluir '{$expectedValue}'"
            );
        }
    }

    #[Test]
    public function it_returns_correct_terminal_statuses(): void
    {
        $terminal = PaymentStatus::paidStatuses();

        $this->assertIsArray($terminal);
        $this->assertContains(PaymentStatus::SUCCEEDED->value, $terminal);
        $this->assertContains(PaymentStatus::OVERPAID->value, $terminal);
        $this->assertContains(PaymentStatus::PAID->value, $terminal);

        $this->assertNotContains(PaymentStatus::DEFAULT->value, $terminal);
        $this->assertNotContains(PaymentStatus::UNDERPAID->value, $terminal);
        $this->assertNotContains(PaymentStatus::UNPAID->value, $terminal);
        $this->assertNotContains(PaymentStatus::REQUIRES_ACTION->value, $terminal);

        $this->assertCount(3, $terminal, 'terminalStatuses() debe devolver exactamente 3 estados');
    }

    #[Test]
    public function it_returns_correct_non_terminal_statuses(): void
    {
        $nonTerminal = PaymentStatus::nonTerminalStatuses();

        $this->assertIsArray($nonTerminal);
        $this->assertContains(PaymentStatus::DEFAULT->value, $nonTerminal);
        $this->assertContains(PaymentStatus::UNDERPAID->value, $nonTerminal);
        $this->assertContains(PaymentStatus::UNPAID->value, $nonTerminal);
        $this->assertContains(PaymentStatus::REQUIRES_ACTION->value, $nonTerminal);

        $this->assertNotContains(PaymentStatus::SUCCEEDED->value, $nonTerminal);
        $this->assertNotContains(PaymentStatus::OVERPAID->value, $nonTerminal);
        $this->assertNotContains(PaymentStatus::PAID->value, $nonTerminal);

        $this->assertCount(4, $nonTerminal, 'nonTerminalStatuses() debe devolver exactamente 4 estados');
    }

    #[Test]
    public function it_returns_correct_non_paid_statuses(): void
    {
        $nonPaid = PaymentStatus::nonPaidStatuses();

        $this->assertIsArray($nonPaid);
        $this->assertContains(PaymentStatus::DEFAULT, $nonPaid);
        $this->assertContains(PaymentStatus::UNPAID, $nonPaid);
        $this->assertContains(PaymentStatus::REQUIRES_ACTION, $nonPaid);

        $this->assertNotContains(PaymentStatus::SUCCEEDED, $nonPaid);
        $this->assertNotContains(PaymentStatus::OVERPAID, $nonPaid);
        $this->assertNotContains(PaymentStatus::PAID, $nonPaid);
        $this->assertNotContains(PaymentStatus::UNDERPAID, $nonPaid);

        $this->assertCount(3, $nonPaid, 'nonPaidStatuses() debe devolver exactamente 3 estados');
    }

    #[Test]
    public function it_returns_correct_reconcilable_statuses(): void
    {
        $reconcilable = PaymentStatus::reconcilableStatuses();

        $this->assertIsArray($reconcilable);
        $this->assertContains(PaymentStatus::DEFAULT, $reconcilable);
        $this->assertContains(PaymentStatus::PAID, $reconcilable);
        $this->assertContains(PaymentStatus::UNDERPAID, $reconcilable);


        $this->assertNotContains(PaymentStatus::SUCCEEDED, $reconcilable);
        $this->assertNotContains(PaymentStatus::OVERPAID, $reconcilable);
        $this->assertNotContains(PaymentStatus::REQUIRES_ACTION, $reconcilable);

        $this->assertCount(3, $reconcilable, 'reconcilableStatuses() debe devolver exactamente 4 estados');
    }

    #[Test]
    public function it_handles_case_insensitive_comparison(): void
    {
        $this->assertEquals(PaymentStatus::DEFAULT, PaymentStatus::from('pending'));

        $this->assertNotEquals(PaymentStatus::DEFAULT, PaymentStatus::tryFrom('PENDING'));
        $this->assertNull(PaymentStatus::tryFrom('PENDING'));
    }

    #[Test]
    public function it_provides_consistent_naming(): void
    {
        $this->assertEquals('SUCCEEDED', PaymentStatus::SUCCEEDED->name);
        $this->assertEquals('succeeded', PaymentStatus::SUCCEEDED->value);

        $this->assertEquals('DEFAULT', PaymentStatus::DEFAULT->name);
        $this->assertEquals('pending', PaymentStatus::DEFAULT->value);

        $this->assertNotEquals('PENDING', PaymentStatus::DEFAULT->name);
    }

    #[Test]
    public function it_can_be_used_in_switch_statements(): void
    {
        $status = PaymentStatus::SUCCEEDED;

        $result = match($status) {
            PaymentStatus::SUCCEEDED => 'exitoso',
            PaymentStatus::OVERPAID => 'sobrepagado',
            PaymentStatus::PAID => 'pagado',
            PaymentStatus::UNPAID => 'no_pagado',
            PaymentStatus::DEFAULT => 'pendiente',
            PaymentStatus::UNDERPAID => 'subpagado',
            PaymentStatus::REQUIRES_ACTION => 'requiere_accion',
        };

        $this->assertEquals('exitoso', $result);
    }

    #[Test]
    public function non_paid_statuses_are_subset_of_non_terminal_statuses(): void
    {
        $nonPaid = PaymentStatus::nonPaidStatuses();
        $nonTerminalValues = PaymentStatus::nonTerminalStatuses();

        $nonPaidValues = array_map(fn($s) => $s->value, $nonPaid);

        foreach ($nonPaidValues as $nonPaidValue) {
            $this->assertContains(
                $nonPaidValue,
                $nonTerminalValues,
                "nonPaid status '{$nonPaidValue}' debería estar en nonTerminalStatuses"
            );
        }
    }

    #[Test]
    public function it_handles_json_serialization(): void
    {
        $status = PaymentStatus::SUCCEEDED;

        $json = json_encode($status);

        $this->assertJson($json);
        $this->assertEquals('"succeeded"', $json);

        $decoded = json_decode($json);
        $restored = PaymentStatus::from($decoded);

        $this->assertEquals($status, $restored);
    }

    #[Test]
    public function it_provides_readable_representation(): void
    {
        $this->assertEquals('pending', PaymentStatus::DEFAULT->value);
        $this->assertEquals('SUCCEEDED', PaymentStatus::SUCCEEDED->name);

        // Para logging o display
        $message = "Payment status: " . PaymentStatus::SUCCEEDED->value;
        $this->assertEquals('Payment status: succeeded', $message);
    }

    #[Test]
    public function all_status_methods_return_consistent_types(): void
    {
        $this->assertIsArray(PaymentStatus::paidStatuses());
        $this->assertIsArray(PaymentStatus::nonTerminalStatuses());
        $this->assertIsArray(PaymentStatus::nonPaidStatuses());
        $this->assertIsArray(PaymentStatus::reconcilableStatuses());

        $terminalFirst = PaymentStatus::paidStatuses()[0];
        $this->assertIsString($terminalFirst);

        $nonPaidFirst = PaymentStatus::nonPaidStatuses()[0];
        $this->assertInstanceOf(PaymentStatus::class, $nonPaidFirst);
    }
}
