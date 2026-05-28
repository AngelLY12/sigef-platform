<?php

namespace Tests\Unit\Domain\Helpers;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Utils\Helpers\Money;
use App\Exceptions\Validation\ValidationException;
use App\Models\PaymentConcept;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MoneyTest extends TestCase
{
    #[Test]
    public function it_creates_money_from_numeric_string(): void
    {
        $money = Money::from('10.50');
        $this->assertSame('10.50', $money->raw());
    }

    #[Test]
    public function it_creates_money_from_integer(): void
    {
        $money = Money::from(100);
        $this->assertSame('100', $money->raw());
    }

    #[Test]
    public function it_creates_money_from_float(): void
    {
        $money = Money::from(25.75);
        $this->assertSame('25.75', $money->raw());
    }

    #[Test]
    public function it_throws_exception_when_amount_is_not_numeric(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El monto debe ser numerico');

        Money::from('abc');
    }

    #[Test]
    public function it_throws_exception_when_amount_is_empty_string(): void
    {
        $this->expectException(ValidationException::class);
        Money::from('');
    }

    // ============================================
    // Comparaciones básicas (positivo, cero, negativo)
    // ============================================

    #[Test]
    public function it_detects_positive_values(): void
    {
        $this->assertTrue(Money::from('10')->isPositive());
        $this->assertTrue(Money::from('0.01')->isPositive());
        $this->assertTrue(Money::from('999999.99')->isPositive());
    }

    #[Test]
    public function it_detects_zero_values(): void
    {
        $this->assertTrue(Money::from('0')->isZero());
        $this->assertTrue(Money::from('0.00')->isZero());
        $this->assertTrue(Money::from('0.000')->isZero());
    }

    #[Test]
    public function it_detects_negative_values(): void
    {
        $this->assertTrue(Money::from('-5')->isNegative());
        $this->assertTrue(Money::from('-0.01')->isNegative());
        $this->assertTrue(Money::from('-999999.99')->isNegative());
    }

    // ============================================
    // Comparaciones entre valores
    // ============================================

    #[Test]
    public function it_compares_greater_than(): void
    {
        $money = Money::from('10.00');

        $this->assertTrue($money->isGreaterThan('5.00'));
        $this->assertTrue($money->isGreaterThan('9.99'));
        $this->assertTrue($money->isGreaterThan(Money::from('9.99')));
        $this->assertFalse($money->isGreaterThan('10.00'));
        $this->assertFalse($money->isGreaterThan('10.01'));
    }

    #[Test]
    public function it_compares_less_than(): void
    {
        $money = Money::from('5.00');

        $this->assertTrue($money->isLessThan('10.00'));
        $this->assertTrue($money->isLessThan('5.01'));
        $this->assertTrue($money->isLessThan(Money::from('5.01')));
        $this->assertFalse($money->isLessThan('5.00'));
        $this->assertFalse($money->isLessThan('4.99'));
    }

    #[Test]
    public function it_compares_equal_to(): void
    {
        $money = Money::from('10.00');

        $this->assertTrue($money->isEqualTo('10.00'));
        $this->assertTrue($money->isEqualTo(Money::from('10.00')));
        $this->assertTrue($money->isEqualTo('10')); // Sin decimales
        $this->assertFalse($money->isEqualTo('10.01'));
        $this->assertFalse($money->isEqualTo('9.99'));
    }

    #[Test]
    public function comparisons_use_two_decimal_precision(): void
    {
        // Con 2 decimales, 10.005 redondea a 10.01, por lo que no es igual a 10.00
        $money = Money::from('10.005');
        $this->assertFalse($money->isEqualTo('10.00'));
        $this->assertTrue($money->isEqualTo('10.01'));

        // 9.995 redondea a 10.00
        $money2 = Money::from('9.995');
        $this->assertFalse($money2->isEqualTo('9.99'));
        $this->assertTrue($money2->isEqualTo('10.00'));
    }

    // ============================================
    // Operaciones Aritméticas
    // ============================================

    #[Test]
    public function it_adds_money_correctly(): void
    {
        $result = Money::from('10.25')->add('5.75');
        $this->assertSame('16.00000000', $result->raw());
        $this->assertSame('16.00', $result->finalize());
    }

    #[Test]
    public function it_adds_money_with_high_precision(): void
    {
        $result = Money::from('0.1')->add('0.2');
        $this->assertSame('0.30000000', $result->raw());
        $this->assertSame('0.30', $result->finalize());
    }

    #[Test]
    public function it_subtracts_money_correctly(): void
    {
        $result = Money::from('20')->sub('7.50');
        $this->assertSame('12.50000000', $result->raw());
        $this->assertSame('12.50', $result->finalize());
    }

    #[Test]
    public function it_multiplies_money_correctly(): void
    {
        $result = Money::from('10')->multiply('1.16');
        $this->assertSame('11.60000000', $result->raw());
        $this->assertSame('11.60', $result->finalize());
    }

    #[Test]
    public function it_divides_money_correctly(): void
    {
        $result = Money::from('10')->divide('4');
        $this->assertSame('2.50000000', $result->raw());
        $this->assertSame('2.50', $result->finalize());
    }

    #[Test]
    public function it_throws_exception_when_dividing_by_zero(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Division by zero in money operation');

        Money::from('10')->divide('0');
    }

    #[Test]
    public function arithmetic_operations_preserve_internal_scale(): void
    {
        // La escala interna es 8, por lo que las operaciones deben mantener esa precisión
        $a = Money::from('1.12345678');
        $b = Money::from('2.87654321');

        $sum = $a->add($b);
        $this->assertSame('3.99999999', $sum->raw()); // 1.12345678 + 2.87654321 = 3.99999999

        $product = $a->multiply('2');
        $this->assertSame('2.24691356', $product->raw()); // 1.12345678 * 2 = 2.24691356
    }

    // ============================================
    // Finalize (Redondeo)
    // ============================================

    #[Test]
    public function finalize_rounds_to_nearest_cent(): void
    {
        // Redondeo al centavo más cercano
        $this->assertSame('100.00', Money::from('99.999')->finalize(2));
        $this->assertSame('99.99', Money::from('99.994')->finalize(2));
        $this->assertSame('1.01', Money::from('1.005')->finalize(2));
        $this->assertSame('1.00', Money::from('1.004')->finalize(2));
        $this->assertSame('0.01', Money::from('0.005')->finalize(2));
        $this->assertSame('0.00', Money::from('0.004')->finalize(2));
    }

    #[Test]
    public function finalize_rounds_negative_numbers_correctly(): void
    {
        // Redondeo de números negativos
        $this->assertSame('-100.00', Money::from('-99.999')->finalize(2));
        $this->assertSame('-99.99', Money::from('-99.994')->finalize(2));
        $this->assertSame('-1.01', Money::from('-1.005')->finalize(2));
        $this->assertSame('-1.00', Money::from('-1.004')->finalize(2));
        $this->assertSame('-0.01', Money::from('-0.005')->finalize(2));
        $this->assertSame('0.00', Money::from('-0.004')->finalize(2)); // -0.004 redondea a 0.00
    }

    #[Test]
    public function finalize_supports_different_scales(): void
    {
        // Diferentes escalas de redondeo
        $this->assertSame('123.46', Money::from('123.456789')->finalize(2));
        $this->assertSame('123.457', Money::from('123.456789')->finalize(3));
        $this->assertSame('123.4568', Money::from('123.456789')->finalize(4));
        $this->assertSame('123.45679', Money::from('123.456789')->finalize(5));
        $this->assertSame('123', Money::from('123.456789')->finalize(0));
    }

    #[Test]
    #[DataProvider('roundingProvider')]
    public function finalize_rounds_correctly_with_data_provider(
        string $input,
        int $scale,
        string $expected
    ): void {
        $result = Money::from($input)->finalize($scale);
        $this->assertSame($expected, $result);
    }

    public static function roundingProvider(): array
    {
        return [
            // Redondeo estándar (half-up)
            ['1.234', 2, '1.23'],
            ['1.235', 2, '1.24'],
            ['1.236', 2, '1.24'],
            ['1.245', 2, '1.25'],
            ['1.255', 2, '1.26'],

            // Negativos
            ['-1.234', 2, '-1.23'],
            ['-1.235', 2, '-1.24'],
            ['-1.236', 2, '-1.24'],

            // Casos borde
            ['0.000', 2, '0.00'],
            ['0.001', 2, '0.00'],
            ['0.005', 2, '0.01'],
            ['0.009', 2, '0.01'],

            // Diferentes escalas
            ['9.9999', 3, '10.000'],
            ['9.9999', 2, '10.00'],
            ['9.9999', 1, '10.0'],
            ['9.9999', 0, '10'],

            // Grandes números
            ['999999.999', 2, '1000000.00'],
            ['999999.994', 2, '999999.99'],
        ];
    }

    // ============================================
    // Conversión a unidades menores
    // ============================================

    #[Test]
    public function it_converts_to_minor_units_correctly(): void
    {
        // Factor por defecto: 100 (centavos)
        $this->assertSame(1099, Money::from('10.99')->toMinorUnits());
        $this->assertSame(1000, Money::from('10.00')->toMinorUnits());
        $this->assertSame(0, Money::from('0.00')->toMinorUnits());
        $this->assertSame(-1099, Money::from('-10.99')->toMinorUnits());

        // Redondeo antes de convertir
        $this->assertSame(1000, Money::from('9.999')->toMinorUnits()); // 9.999 redondea a 10.00
        $this->assertSame(995, Money::from('9.949')->toMinorUnits());  // 9.949 redondea a 9.95
    }

    #[Test]
    public function toMinorUnits_supports_different_factors(): void
    {
        // Factor 1000 (por ejemplo, para milésimas)
        $this->assertSame(10995, Money::from('10.995')->toMinorUnits(1000));
        $this->assertSame(10000, Money::from('10.00')->toMinorUnits(1000));

        // Factor 1 (sin conversión, solo redondeo a entero)
        $this->assertSame(11, Money::from('10.995')->toMinorUnits(1));
        $this->assertSame(10, Money::from('10.004')->toMinorUnits(1));
    }

    // ============================================
    // Método raw()
    // ============================================

    #[Test]
    public function raw_returns_internal_representation(): void
    {
        // raw() devuelve el valor interno con 8 decimales
        $this->assertSame('10.5', Money::from('10.5')->raw());
        $this->assertSame('123.45678', Money::from('123.45678')->raw());
        $this->assertSame('0.1', Money::from('0.1')->raw());

    }


    #[Test]
    public function it_handles_large_numbers(): void
    {
        $large = Money::from('999999999.99')->add('0.01');
        $this->assertSame('1000000000.00', $large->finalize(2));

        $product = Money::from('1000000')->multiply('2.5');
        $this->assertSame('2500000.00', $product->finalize(2));
    }

    #[Test]
    public function it_handles_small_numbers(): void
    {
        $small = Money::from('0.00000001');
        $this->assertSame('0.00', $small->finalize(2));

        // Aún con escala interna de 8, un número muy pequeño se redondea a 0.00
        $sum = Money::from('0.00000001')->add('0.00000001');
        $this->assertSame('0.00', $sum->finalize(2));
    }

    #[Test]
    public function operations_can_be_chained(): void
    {
        $result = Money::from('100')
            ->add('50')
            ->sub('30')
            ->multiply('1.1')
            ->divide('2');

        $this->assertSame('66.00000000', $result->raw());
        $this->assertSame('66.00', $result->finalize(2));

        // Cálculo: ((100 + 50 - 30) * 1.1) / 2 = (120 * 1.1) / 2 = 132 / 2 = 66
    }

    #[Test]
    public function it_handles_partial_payments(): void
    {
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Partial concept',
            'description' => 'Partial concepto test',
            'status' => PaymentConceptStatus::ACTIVO,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(5),
            'amount' => '605.50',
            'applies_to' => PaymentConceptAppliesTo::TODOS,
        ]);

        $partial = 6;
        $pagado = Money::from('0.00');
        $divide = Money::from($concept->amount)->divide($partial);

        for($i = 1; $i <= $partial; $i++)
        {
            $pagado = $pagado->add($divide);
            echo "current: {$pagado->raw()}\n";
        }


        $this->assertEquals($concept->amount, $pagado->finalize());

    }

    #[Test]
    public function it_calculates_exact_partial_payments_without_remainder(): void
    {
        // 600.00 dividido en 6 pagos exactos de 100.00
        $total = Money::from('600.00');
        $partials = 6;

        $partialPayment = $total->divide($partials);
        $accumulated = Money::from('0.00');

        for ($i = 0; $i < $partials; $i++) {
            $accumulated = $accumulated->add($partialPayment);
        }

        $this->assertEquals('600.00', $accumulated->finalize());
        $this->assertEquals('100.00', $partialPayment->finalize());
        $this->assertTrue($total->isEqualTo($accumulated->finalize()));
    }

    #[Test]
    public function it_calculates_partial_payments_with_precision(): void
    {
        // 605.50 dividido en 6 pagos
        $total = Money::from('605.50');
        $partials = 6;

        $partialPayment = $total->divide($partials);
        $accumulated = Money::from('0.00');

        $paymentLog = [];
        for ($i = 0; $i < $partials; $i++) {
            $accumulated = $accumulated->add($partialPayment);
            $paymentLog[] = [
                'payment' => $i + 1,
                'partial_raw' => $partialPayment->raw(),
                'accumulated_raw' => $accumulated->raw(),
                'accumulated_final' => $accumulated->finalize(),
            ];
        }

        // La suma final debe ser igual al total original
        $this->assertEquals('605.50', $accumulated->finalize());

        // Verificar valores raw durante el proceso
        $this->assertCount($partials, $paymentLog);

        // El último pago acumulado debe ser igual al total
        $lastPayment = end($paymentLog);
        $this->assertEquals('605.50', $lastPayment['accumulated_final']);
    }

    #[Test]
    public function it_maintains_precision_with_repeated_division_and_addition(): void
    {
        // Casos difíciles de redondeo
        $testCases = [
            ['amount' => '0.01', 'partials' => 3, 'expected_total' => '0.01'],
            ['amount' => '0.03', 'partials' => 4, 'expected_total' => '0.03'],
            ['amount' => '100.01', 'partials' => 7, 'expected_total' => '100.01'],
            ['amount' => '999.99', 'partials' => 9, 'expected_total' => '999.99'],
        ];

        foreach ($testCases as $case) {
            $total = Money::from($case['amount']);
            $partials = $case['partials'];

            $partialPayment = $total->divide($partials);
            $accumulated = Money::from('0.00');

            for ($i = 0; $i < $partials; $i++) {
                $accumulated = $accumulated->add($partialPayment);
            }

            $this->assertEquals(
                $case['expected_total'],
                $accumulated->finalize(),
                sprintf('Failed for %s divided by %d', $case['amount'], $partials)
            );
        }
    }

    #[Test]
    public function it_handles_different_numbers_of_partial_payments(): void
    {
        $total = Money::from('1000.00');

        $paymentCounts = [1, 2, 4, 5, 10, 20, 25, 50, 100];

        foreach ($paymentCounts as $partials) {
            $partialPayment = $total->divide($partials);
            $accumulated = Money::from('0.00');

            for ($i = 0; $i < $partials; $i++) {
                $accumulated = $accumulated->add($partialPayment);
            }

            $this->assertEquals(
                '1000.00',
                $accumulated->finalize(),
                sprintf('Failed for %d partial payments', $partials)
            );
        }
    }

    #[Test]
    public function it_correctly_tracks_raw_values_during_partial_payments(): void
    {
        $total = Money::from('605.50');
        $partials = 6;

        $partialPayment = $total->divide($partials);
        $accumulated = Money::from('0.00');

        $rawValues = [];
        for ($i = 0; $i < $partials; $i++) {
            $accumulated = $accumulated->add($partialPayment);
            $rawValues[] = [
                'iteration' => $i + 1,
                'partial_raw' => $partialPayment->raw(),
                'accumulated_raw' => $accumulated->raw(),
            ];
        }

        // Verificar que el último accumulated_raw convertido a finalize sea correcto
        $finalAccumulated = $accumulated->finalize();
        $this->assertEquals('605.50', $finalAccumulated);

        // Los raw values deben ser consistentes
        $lastRaw = end($rawValues);
        $lastAccumulatedFromRaw = Money::from($lastRaw['accumulated_raw'])->finalize();
        $this->assertEquals('605.50', $lastAccumulatedFromRaw);
    }

    #[Test]
    public function it_handles_negative_amounts_in_partial_payments(): void
    {
        // Deudas (montos negativos)
        $total = Money::from('-605.50');
        $partials = 6;

        $partialPayment = $total->divide($partials);
        $accumulated = Money::from('0.00');

        for ($i = 0; $i < $partials; $i++) {
            $accumulated = $accumulated->add($partialPayment);
        }

        $this->assertEquals('-605.50', $accumulated->finalize());
        $this->assertTrue($accumulated->isNegative());

        // Verificar que cada pago parcial es negativo
        $this->assertTrue($partialPayment->isNegative());
    }

    #[Test]
    public function it_maintains_immutability_during_partial_payment_calculations(): void
    {
        // Verificar que las operaciones no modifican objetos originales
        $originalTotal = Money::from('1000.00');
        $originalZero = Money::from('0.00');

        $partialPayment = $originalTotal->divide(4);
        $accumulated1 = $originalZero->add($partialPayment);
        $accumulated2 = $accumulated1->add($partialPayment);

        // Los originales no deben cambiar
        $this->assertEquals('1000.00', $originalTotal->finalize());
        $this->assertEquals('0.00', $originalZero->finalize());

        // Cada operación retorna nueva instancia
        $this->assertNotSame($originalZero, $accumulated1);
        $this->assertNotSame($accumulated1, $accumulated2);

        // Valores correctos
        $this->assertEquals('250.00', $partialPayment->finalize());
        $this->assertEquals('250.00', $accumulated1->finalize());
        $this->assertEquals('500.00', $accumulated2->finalize());
    }

    #[Test]
    public function it_correctly_handles_large_amounts_with_partial_payments(): void
    {
        $largeAmounts = [
            '1000000.00',
            '999999.99',
            '1234567.89',
            '5000000.00',
        ];

        foreach ($largeAmounts as $amount) {
            $total = Money::from($amount);
            $partials = 7; // Número primo para forzar decimales

            $partialPayment = $total->divide($partials);
            $accumulated = Money::from('0.00');

            for ($i = 0; $i < $partials; $i++) {
                $accumulated = $accumulated->add($partialPayment);
            }

            $this->assertEquals(
                $amount,
                $accumulated->finalize(),
                sprintf('Failed for amount: %s', $amount)
            );
        }
    }

    #[Test]
    public function it_provides_consistent_results_for_repeated_partial_payment_calculations(): void
    {
        $total = Money::from('605.50');
        $partials = 6;

        // Primera ejecución
        $partial1 = $total->divide($partials);
        $accumulated1 = Money::from('0.00');
        for ($i = 0; $i < $partials; $i++) {
            $accumulated1 = $accumulated1->add($partial1);
        }

        // Segunda ejecución (debe dar exactamente lo mismo)
        $partial2 = $total->divide($partials);
        $accumulated2 = Money::from('0.00');
        for ($i = 0; $i < $partials; $i++) {
            $accumulated2 = $accumulated2->add($partial2);
        }

        // Los resultados deben ser idénticos
        $this->assertEquals($partial1->raw(), $partial2->raw());
        $this->assertEquals($partial1->finalize(), $partial2->finalize());
        $this->assertEquals($accumulated1->raw(), $accumulated2->raw());
        $this->assertEquals($accumulated1->finalize(), $accumulated2->finalize());
        $this->assertEquals('605.50', $accumulated1->finalize());
    }

    #[Test]
    public function it_correctly_converts_to_minor_units_after_partial_payments(): void
    {
        $total = Money::from('605.50');
        $partials = 6;

        $partialPayment = $total->divide($partials);
        $accumulated = Money::from('0.00');

        for ($i = 0; $i < $partials; $i++) {
            $accumulated = $accumulated->add($partialPayment);
        }

        // Convertir a unidades menores (centavos)
        $totalInCents = $total->toMinorUnits();
        $accumulatedInCents = $accumulated->toMinorUnits();

        $this->assertEquals(60550, $totalInCents);
        $this->assertEquals(60550, $accumulatedInCents);

        // Verificar que la conversión es reversible
        $this->assertEquals(
            $total->finalize(),
            Money::from($accumulatedInCents / 100)->finalize()
        );
    }

    #[Test]
    public function it_handles_complex_partial_payment_scenarios(): void
    {
        // Escenario complejo: diferentes porcentajes y luego divisiones
        $total = Money::from('1000.00');

        // Primer pago: 30%
        $firstPayment = $total->multiply('0.30');
        $remaining = $total->sub($firstPayment);

        // Dividir el resto en 5 pagos
        $remainingPayments = 5;
        $partialPayment = $remaining->divide($remainingPayments);

        $accumulated = $firstPayment;
        for ($i = 0; $i < $remainingPayments; $i++) {
            $accumulated = $accumulated->add($partialPayment);
        }

        $this->assertEquals('1000.00', $accumulated->finalize());
        $this->assertEquals('300.00', $firstPayment->finalize());

        // Verificar que cada pago del resto es consistente
        $expectedPartial = $remaining->divide($remainingPayments)->finalize();
        $this->assertEquals($expectedPartial, $partialPayment->finalize());
    }

    #[Test]
    public function it_handles_single_partial_payment_correctly(): void
    {
        // Caso límite: un solo pago
        $total = Money::from('605.50');
        $partials = 1;

        $partialPayment = $total->divide($partials);
        $accumulated = Money::from('0.00')->add($partialPayment);

        $this->assertEquals('605.50', $accumulated->finalize());
        $this->assertEquals('605.50', $partialPayment->finalize());
        $this->assertTrue($total->isEqualTo($accumulated));
    }




    #[Test]
    public function it_maintains_consistency_between_raw_and_finalize(): void
    {
        $money = Money::from('123.456789');

        // raw() muestra la representación interna (8 decimales)
        $this->assertSame('123.456789', $money->raw());

        // finalize() con diferentes escalas
        $this->assertSame('123.46', $money->finalize(2));
        $this->assertSame('123.457', $money->finalize(3));
        $this->assertSame('123.4568', $money->finalize(4));

        // Después de una operación, se mantiene la escala interna
        $doubled = $money->multiply('2');
        $this->assertSame('246.91357800', $doubled->raw());
        $this->assertSame('246.91', $doubled->finalize(2));
    }

    #[Test]
    #[DataProvider('additionProvider')]
    public function it_adds_correctly_with_data_provider(
        string $a,
        string $b,
        string $expectedRaw,
        string $expectedFinalized
    ): void {
        $result = Money::from($a)->add($b);
        $this->assertSame($expectedRaw, $result->raw());
        $this->assertSame($expectedFinalized, $result->finalize(2));
    }

    public static function additionProvider(): array
    {
        return [
            ['0.00', '0.00', '0.00000000', '0.00'],
            ['1.50', '2.50', '4.00000000', '4.00'],
            ['-1.50', '3.50', '2.00000000', '2.00'],
            ['99.99', '0.01', '100.00000000', '100.00'],
            ['0.01', '-0.01', '0.00000000', '0.00'],
            ['123.456', '789.012', '912.46800000', '912.47'], // 123.456 + 789.012 = 912.468
        ];
    }

    #[Test]
    #[DataProvider('multiplicationProvider')]
    public function it_multiplies_correctly_with_data_provider(
        string $a,
        string $b,
        string $expectedRaw,
        string $expectedFinalized
    ): void {
        $result = Money::from($a)->multiply($b);
        $this->assertSame($expectedRaw, $result->raw());
        $this->assertSame($expectedFinalized, $result->finalize(2));
    }

    public static function multiplicationProvider(): array
    {
        return [
            ['10.00', '1.00', '10.00000000', '10.00'],
            ['10.00', '0.10', '1.00000000', '1.00'],
            ['10.00', '0.01', '0.10000000', '0.10'],
            ['100.00', '1.16', '116.00000000', '116.00'], // IVA 16%
            ['-100.00', '1.16', '-116.00000000', '-116.00'],
            ['7.50', '3', '22.50000000', '22.50'],
        ];
    }


}
