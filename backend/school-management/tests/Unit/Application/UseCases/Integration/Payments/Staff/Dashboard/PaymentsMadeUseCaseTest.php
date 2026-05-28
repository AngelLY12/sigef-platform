<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Staff\Dashboard;

use App\Core\Application\DTO\Response\Payment\FinancialSummaryResponse;
use App\Core\Application\UseCases\Payments\Staff\Dashboard\PaymentsMadeUseCase;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class PaymentsMadeUseCaseTest extends TestCase
{
    use DatabaseTransactions;

    private PaymentsMadeUseCase $useCase;
    private MockInterface $paymentQueryRepoMock;
    private MockInterface $stripeGatewayMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear mocks para las dependencias
        $this->paymentQueryRepoMock = Mockery::mock(PaymentQueryRepInterface::class);
        $this->stripeGatewayMock = Mockery::mock(StripeGatewayQueryInterface::class);

        // Crear instancia del use case con los mocks
        $this->useCase = new PaymentsMadeUseCase(
            $this->paymentQueryRepoMock,
            $this->stripeGatewayMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_financial_summary_for_current_year(): void
    {
        // Arrange
        $onlyThisYear = true;

        // Datos simulados de pagos
        $grossData = [
            'total' => '15000.00',
            'by_month' => [
                '2024-01' => '1000.00',
                '2024-02' => '2000.00',
                '2024-03' => '1500.00',
                '2024-07' => '2500.00',
                '2024-08' => '3000.00',
                '2024-09' => '2000.00',
                '2024-10' => '3000.00',
            ]
        ];

        // Datos simulados de balance de Stripe
        $balanceData = [
            'available' => [
                [
                    'amount' => '5000.00',
                    'source_types' => ['card' => '4000.00', 'bank_transfer' => '1000.00']
                ]
            ],
            'pending' => [
                [
                    'amount' => '2000.00',
                    'source_types' => ['card' => '1500.00', 'bank_transfer' => '500.00']
                ]
            ]
        ];

        // Datos simulados de payouts de Stripe
        $payoutsData = [
            'total' => '8000.00',
            'total_fee' => '200.00',
            'by_month' => [
                '2024-01' => ['amount' => '1000.00', 'fee' => '25.00'],
                '2024-02' => ['amount' => '2000.00', 'fee' => '50.00'],
                '2024-07' => ['amount' => '3000.00', 'fee' => '75.00'],
                '2024-08' => ['amount' => '2000.00', 'fee' => '50.00'],
            ]
        ];

        // Configurar mocks
        $this->paymentQueryRepoMock
            ->shouldReceive('getAllPaymentsMade')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($grossData);

        $this->stripeGatewayMock
            ->shouldReceive('getBalanceFromStripe')
            ->once()
            ->andReturn($balanceData);

        $this->stripeGatewayMock
            ->shouldReceive('getPayoutsFromStripe')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($payoutsData);

        // Act
        $response = $this->useCase->execute($onlyThisYear);

        // Assert
        $this->assertInstanceOf(FinancialSummaryResponse::class, $response);

        // Verificar totales
        $this->assertEquals('15000.00', $response->totalPayments);
        $this->assertEquals('8000.00', $response->totalPayouts);
        $this->assertEquals('200.00', $response->totalFees);

        // Verificar balances
        $this->assertEquals('5000.00', $response->totalBalanceAvailable);
        $this->assertEquals('2000.00', $response->totalBalancePending);

        // Verificar balances por fuente
        $this->assertEquals('4000.00', $response->totalBalanceAvailableBySource['card']);
        $this->assertEquals('1000.00', $response->totalBalanceAvailableBySource['bank_transfer']);
        $this->assertEquals('1500.00', $response->totalBalancePendingBySource['card']);
        $this->assertEquals('500.00', $response->totalBalancePendingBySource['bank_transfer']);

        // Verificar porcentajes (calculados: 5000/15000=33.33%, 2000/15000=13.33%, etc.)
        $this->assertEquals('33.33', $response->availablePercentage); // 5000/15000 = 33.33%
        $this->assertEquals('13.33', $response->pendingPercentage);   // 2000/15000 = 13.33%
        $this->assertEquals('53.33', $response->netReceivedPercentage); // 8000/15000 = 53.33%
        $this->assertEquals('1.33', $response->feePercentage);         // 200/15000 = 1.33%

        // Verificar agrupación por semestre para pagos
        $this->assertArrayHasKey('2024-H1', $response->paymentsBySemester);
        $this->assertArrayHasKey('2024-H2', $response->paymentsBySemester);

        // Verificar totales por semestre para pagos
        $this->assertEquals('4500.00', $response->paymentsBySemester['2024-H1']['total']); // 1000+2000+1500
        $this->assertEquals('10500.00', $response->paymentsBySemester['2024-H2']['total']); // 2500+3000+2000+3000

        // Verificar agrupación por semestre para payouts
        $this->assertArrayHasKey('2024-H1', $response->payoutsBySemester);
        $this->assertArrayHasKey('2024-H2', $response->payoutsBySemester);

        // Verificar totales por semestre para payouts
        $this->assertEquals('3000.00', $response->payoutsBySemester['2024-H1']['total']); // 1000+2000
        $this->assertEquals('5000.00', $response->payoutsBySemester['2024-H2']['total']); // 3000+2000

        // Verificar fees por semestre
        $this->assertEquals('75.00', $response->payoutsBySemester['2024-H1']['total_fee']); // 25+50
        $this->assertEquals('125.00', $response->payoutsBySemester['2024-H2']['total_fee']); // 75+50
    }

    #[Test]
    public function it_returns_financial_summary_for_all_years(): void
    {
        // Arrange
        $onlyThisYear = false;

        // Datos simulados con múltiples años
        $grossData = [
            'total' => '25000.00',
            'by_month' => [
                '2023-11' => '1000.00',
                '2023-12' => '1500.00',
                '2024-01' => '2000.00',
                '2024-06' => '3000.00',
                '2024-07' => '2500.00',
            ]
        ];

        $balanceData = [
            'available' => [
                ['amount' => '6000.00', 'source_types' => ['card' => '6000.00']]
            ],
            'pending' => [
                ['amount' => '1000.00', 'source_types' => ['card' => '1000.00']]
            ]
        ];

        $payoutsData = [
            'total' => '12000.00',
            'total_fee' => '300.00',
            'by_month' => [
                '2023-11' => ['amount' => '500.00', 'fee' => '15.00'],
                '2024-01' => ['amount' => '2000.00', 'fee' => '50.00'],
                '2024-07' => ['amount' => '1500.00', 'fee' => '40.00'],
            ]
        ];

        // Configurar mocks
        $this->paymentQueryRepoMock
            ->shouldReceive('getAllPaymentsMade')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($grossData);

        $this->stripeGatewayMock
            ->shouldReceive('getBalanceFromStripe')
            ->once()
            ->andReturn($balanceData);

        $this->stripeGatewayMock
            ->shouldReceive('getPayoutsFromStripe')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($payoutsData);

        // Act
        $response = $this->useCase->execute($onlyThisYear);

        // Assert
        $this->assertInstanceOf(FinancialSummaryResponse::class, $response);

        // Verificar que incluye múltiples años
        $this->assertArrayHasKey('2023-H2', $response->paymentsBySemester);
        $this->assertArrayHasKey('2024-H1', $response->paymentsBySemester);
        $this->assertArrayHasKey('2024-H2', $response->paymentsBySemester);

        // Verificar totales por semestre
        $this->assertEquals('2500.00', $response->paymentsBySemester['2023-H2']['total']); // 1000+1500
        $this->assertEquals('5000.00', $response->paymentsBySemester['2024-H1']['total']); // 2000+3000
        $this->assertEquals('2500.00', $response->paymentsBySemester['2024-H2']['total']); // 2500
    }

    #[Test]
    public function it_handles_empty_payments_data(): void
    {
        // Arrange
        $onlyThisYear = true;

        // Datos vacíos
        $grossData = [
            'total' => '0.00',
            'by_month' => []
        ];

        $balanceData = [
            'available' => [],
            'pending' => []
        ];

        $payoutsData = [
            'total' => '0.00',
            'total_fee' => '0.00',
            'by_month' => []
        ];

        // Configurar mocks
        $this->paymentQueryRepoMock
            ->shouldReceive('getAllPaymentsMade')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($grossData);

        $this->stripeGatewayMock
            ->shouldReceive('getBalanceFromStripe')
            ->once()
            ->andReturn($balanceData);

        $this->stripeGatewayMock
            ->shouldReceive('getPayoutsFromStripe')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($payoutsData);

        // Act
        $response = $this->useCase->execute($onlyThisYear);

        // Assert
        $this->assertInstanceOf(FinancialSummaryResponse::class, $response);

        // Todos los valores deberían ser cero
        $this->assertEquals('0.00', $response->totalPayments);
        $this->assertEquals('0.00', $response->totalPayouts);
        $this->assertEquals('0.00', $response->totalFees);
        $this->assertEquals('0.00', $response->totalBalanceAvailable);
        $this->assertEquals('0.00', $response->totalBalancePending);

        // Porcentajes deberían ser cero cuando no hay pagos
        $this->assertEquals('0.00', $response->availablePercentage);
        $this->assertEquals('0.00', $response->pendingPercentage);
        $this->assertEquals('0.00', $response->netReceivedPercentage);
        $this->assertEquals('0.00', $response->feePercentage);

        // Arrays vacíos
        $this->assertEmpty($response->paymentsBySemester);
        $this->assertEmpty($response->payoutsBySemester);
        $this->assertEmpty($response->totalBalanceAvailableBySource);
        $this->assertEmpty($response->totalBalancePendingBySource);
    }

    #[Test]
    public function it_handles_multiple_balance_entries(): void
    {
        // Arrange
        $onlyThisYear = true;

        $grossData = [
            'total' => '10000.00',
            'by_month' => ['2024-01' => '10000.00']
        ];

        // Múltiples entradas de balance
        $balanceData = [
            'available' => [
                ['amount' => '1000.00', 'source_types' => ['card' => '800.00', 'bank_transfer' => '200.00']],
                ['amount' => '2000.00', 'source_types' => ['card' => '1500.00', 'paypal' => '500.00']],
            ],
            'pending' => [
                ['amount' => '500.00', 'source_types' => ['card' => '500.00']],
                ['amount' => '300.00', 'source_types' => ['bank_transfer' => '300.00']],
            ]
        ];

        $payoutsData = [
            'total' => '5000.00',
            'total_fee' => '100.00',
            'by_month' => ['2024-01' => ['amount' => '5000.00', 'fee' => '100.00']]
        ];

        // Configurar mocks
        $this->paymentQueryRepoMock
            ->shouldReceive('getAllPaymentsMade')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($grossData);

        $this->stripeGatewayMock
            ->shouldReceive('getBalanceFromStripe')
            ->once()
            ->andReturn($balanceData);

        $this->stripeGatewayMock
            ->shouldReceive('getPayoutsFromStripe')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($payoutsData);

        // Act
        $response = $this->useCase->execute($onlyThisYear);

        // Assert
        // Total disponible: 1000 + 2000 = 3000
        $this->assertEquals('3000.00', $response->totalBalanceAvailable);

        // Total pendiente: 500 + 300 = 800
        $this->assertEquals('800.00', $response->totalBalancePending);

        // Verificar suma por fuente para available
        $this->assertEquals('2300.00', $response->totalBalanceAvailableBySource['card']); // 800 + 1500
        $this->assertEquals('200.00', $response->totalBalanceAvailableBySource['bank_transfer']);
        $this->assertEquals('500.00', $response->totalBalanceAvailableBySource['paypal']);

        // Verificar suma por fuente para pending
        $this->assertEquals('500.00', $response->totalBalancePendingBySource['card']);
        $this->assertEquals('300.00', $response->totalBalancePendingBySource['bank_transfer']);
    }

    #[Test]
    public function it_calculates_correct_semester_grouping(): void
    {
        // Arrange
        $onlyThisYear = true;

        // Datos que cruzan años y semestres
        $grossData = [
            'total' => '10000.00',
            'by_month' => [
                '2023-12' => '1000.00', // H2 2023
                '2024-01' => '2000.00', // H1 2024
                '2024-06' => '3000.00', // H1 2024
                '2024-07' => '4000.00', // H2 2024
            ]
        ];

        $balanceData = [
            'available' => [],
            'pending' => []
        ];

        $payoutsData = [
            'total' => '0.00',
            'total_fee' => '0.00',
            'by_month' => []
        ];

        // Configurar mocks
        $this->paymentQueryRepoMock
            ->shouldReceive('getAllPaymentsMade')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($grossData);

        $this->stripeGatewayMock
            ->shouldReceive('getBalanceFromStripe')
            ->once()
            ->andReturn($balanceData);

        $this->stripeGatewayMock
            ->shouldReceive('getPayoutsFromStripe')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($payoutsData);

        // Act
        $response = $this->useCase->execute($onlyThisYear);

        // Assert
        // Verificar que los meses se agrupan correctamente por semestre
        $this->assertArrayHasKey('2023-H2', $response->paymentsBySemester);
        $this->assertArrayHasKey('2024-H1', $response->paymentsBySemester);
        $this->assertArrayHasKey('2024-H2', $response->paymentsBySemester);

        // Verificar totales por semestre
        $this->assertEquals('1000.00', $response->paymentsBySemester['2023-H2']['total']);
        $this->assertEquals('5000.00', $response->paymentsBySemester['2024-H1']['total']); // 2000 + 3000
        $this->assertEquals('4000.00', $response->paymentsBySemester['2024-H2']['total']);

        // Verificar que los meses están en el array months
        $this->assertCount(1, $response->paymentsBySemester['2023-H2']['months']);
        $this->assertCount(2, $response->paymentsBySemester['2024-H1']['months']);
        $this->assertCount(1, $response->paymentsBySemester['2024-H2']['months']);
    }

    #[Test]
    public function it_calculates_percentages_correctly(): void
    {
        // Arrange
        $onlyThisYear = true;

        // Datos para probar cálculos de porcentaje
        $grossData = [
            'total' => '10000.00', // Base para porcentajes
            'by_month' => ['2024-01' => '10000.00']
        ];

        $balanceData = [
            'available' => [['amount' => '2500.00', 'source_types' => []]], // 25% de 10000
            'pending' => [['amount' => '1500.00', 'source_types' => []]],    // 15% de 10000
        ];

        $payoutsData = [
            'total' => '6000.00',      // 60% de 10000
            'total_fee' => '300.00',   // 3% de 10000
            'by_month' => ['2024-01' => ['amount' => '6000.00', 'fee' => '300.00']]
        ];

        // Configurar mocks
        $this->paymentQueryRepoMock
            ->shouldReceive('getAllPaymentsMade')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($grossData);

        $this->stripeGatewayMock
            ->shouldReceive('getBalanceFromStripe')
            ->once()
            ->andReturn($balanceData);

        $this->stripeGatewayMock
            ->shouldReceive('getPayoutsFromStripe')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($payoutsData);

        // Act
        $response = $this->useCase->execute($onlyThisYear);

        // Assert - Verificar porcentajes redondeados a 2 decimales
        $this->assertEquals('25.00', $response->availablePercentage);  // 2500/10000 * 100 = 25%
        $this->assertEquals('15.00', $response->pendingPercentage);    // 1500/10000 * 100 = 15%
        $this->assertEquals('60.00', $response->netReceivedPercentage); // 6000/10000 * 100 = 60%
        $this->assertEquals('3.00', $response->feePercentage);         // 300/10000 * 100 = 3%
    }

    #[Test]
    public function it_handles_zero_total_payments_for_percentage_calculation(): void
    {
        // Arrange
        $onlyThisYear = true;

        // Cuando totalPayments es 0, todos los porcentajes deben ser 0.00
        $grossData = [
            'total' => '0.00',
            'by_month' => []
        ];

        $balanceData = [
            'available' => [['amount' => '1000.00', 'source_types' => []]],
            'pending' => [['amount' => '500.00', 'source_types' => []]],
        ];

        $payoutsData = [
            'total' => '2000.00',
            'total_fee' => '100.00',
            'by_month' => []
        ];

        // Configurar mocks
        $this->paymentQueryRepoMock
            ->shouldReceive('getAllPaymentsMade')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($grossData);

        $this->stripeGatewayMock
            ->shouldReceive('getBalanceFromStripe')
            ->once()
            ->andReturn($balanceData);

        $this->stripeGatewayMock
            ->shouldReceive('getPayoutsFromStripe')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($payoutsData);

        // Act
        $response = $this->useCase->execute($onlyThisYear);

        // Assert - Todos los porcentajes deben ser 0.00 cuando totalPayments es 0
        $this->assertEquals('0.00', $response->availablePercentage);
        $this->assertEquals('0.00', $response->pendingPercentage);
        $this->assertEquals('0.00', $response->netReceivedPercentage);
        $this->assertEquals('0.00', $response->feePercentage);

        // Pero los valores absolutos deben mantenerse
        $this->assertEquals('1000.00', $response->totalBalanceAvailable);
        $this->assertEquals('500.00', $response->totalBalancePending);
        $this->assertEquals('2000.00', $response->totalPayouts);
        $this->assertEquals('100.00', $response->totalFees);
    }

    #[Test]
    public function it_handles_payouts_by_semester_grouping(): void
    {
        // Arrange
        $onlyThisYear = true;

        $grossData = [
            'total' => '10000.00',
            'by_month' => ['2024-01' => '10000.00']
        ];

        $balanceData = [
            'available' => [],
            'pending' => []
        ];

        // Payouts en diferentes semestres
        $payoutsData = [
            'total' => '6000.00',
            'total_fee' => '150.00',
            'by_month' => [
                '2024-01' => ['amount' => '1000.00', 'fee' => '25.00'], // H1
                '2024-02' => ['amount' => '2000.00', 'fee' => '50.00'], // H1
                '2024-07' => ['amount' => '2000.00', 'fee' => '50.00'], // H2
                '2024-08' => ['amount' => '1000.00', 'fee' => '25.00'], // H2
            ]
        ];

        // Configurar mocks
        $this->paymentQueryRepoMock
            ->shouldReceive('getAllPaymentsMade')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($grossData);

        $this->stripeGatewayMock
            ->shouldReceive('getBalanceFromStripe')
            ->once()
            ->andReturn($balanceData);

        $this->stripeGatewayMock
            ->shouldReceive('getPayoutsFromStripe')
            ->with($onlyThisYear)
            ->once()
            ->andReturn($payoutsData);

        // Act
        $response = $this->useCase->execute($onlyThisYear);

        // Assert
        // Verificar agrupación de payouts por semestre
        $this->assertArrayHasKey('2024-H1', $response->payoutsBySemester);
        $this->assertArrayHasKey('2024-H2', $response->payoutsBySemester);

        // Totales por semestre
        $this->assertEquals('3000.00', $response->payoutsBySemester['2024-H1']['total']); // 1000 + 2000
        $this->assertEquals('3000.00', $response->payoutsBySemester['2024-H2']['total']); // 2000 + 1000

        // Fees por semestre
        $this->assertEquals('75.00', $response->payoutsBySemester['2024-H1']['total_fee']); // 25 + 50
        $this->assertEquals('75.00', $response->payoutsBySemester['2024-H2']['total_fee']); // 50 + 25

        // Verificar meses en cada semestre
        $this->assertCount(2, $response->payoutsBySemester['2024-H1']['months']);
        $this->assertCount(2, $response->payoutsBySemester['2024-H2']['months']);
    }

}
