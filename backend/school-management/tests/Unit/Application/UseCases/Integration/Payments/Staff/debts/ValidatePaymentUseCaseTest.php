<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Staff\debts;

use App\Core\Application\DTO\Response\General\ReconciliationResult;
use App\Core\Application\DTO\Response\Payment\PaymentValidateResponse;
use App\Core\Application\Services\Payments\Staff\PaymentValidationService;
use App\Core\Application\UseCases\Payments\Reconcile\ReconcilePaymentsForceUseCase;
use App\Core\Application\UseCases\Payments\Staff\Debts\ValidatePaymentUseCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Core\Infraestructure\Mappers\PaymentConceptMapper;
use App\Core\Infraestructure\Mappers\PaymentMethodMapper;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Events\PaymentReconciledEvent;
use App\Exceptions\NotFound\UserNotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\Jobs\ClearStaffCacheJob;
use App\Jobs\ClearStudentCacheJob;
use App\Jobs\SendMailJob;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\PaymentConcept;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentEvent;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;
use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event as LaravelEvent;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class ValidatePaymentUseCaseTest extends TestCase
{
    private PaymentValidationService $service;
    private MockInterface $userQueryRepoMock;
    private MockInterface $paymentQueryRepoMock;
    private MockInterface $paymentRepoMock;
    private MockInterface $stripeGatewayMock;
    private MockInterface $paymentMethodQueryRepoMock;
    private MockInterface $paymentConceptQueryRepoMock;
    private MockInterface $paymentEventQueryRepoMock;
    private MockInterface $reconcileUseCaseMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear mocks para todas las dependencias
        $this->userQueryRepoMock = Mockery::mock(UserQueryRepInterface::class);
        $this->paymentQueryRepoMock = Mockery::mock(PaymentQueryRepInterface::class);
        $this->paymentRepoMock = Mockery::mock(PaymentRepInterface::class);
        $this->stripeGatewayMock = Mockery::mock(StripeGatewayQueryInterface::class);
        $this->paymentMethodQueryRepoMock = Mockery::mock(PaymentMethodQueryRepInterface::class);
        $this->paymentConceptQueryRepoMock = Mockery::mock(PaymentConceptQueryRepInterface::class);
        $this->paymentEventQueryRepoMock = Mockery::mock(PaymentEventQueryRepInterface::class);
        $this->reconcileUseCaseMock = Mockery::mock(ReconcilePaymentsForceUseCase::class);

        // Crear el servicio con mocks
        $this->service = new PaymentValidationService(
            $this->userQueryRepoMock,
            $this->paymentRepoMock,
            $this->paymentQueryRepoMock,
            $this->stripeGatewayMock,
            $this->paymentMethodQueryRepoMock,
            $this->paymentConceptQueryRepoMock,
            $this->paymentEventQueryRepoMock,
            $this->reconcileUseCaseMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_throws_exception_when_user_not_found(): void
    {
        // Arrange
        $search = 'S99999999';
        $paymentIntentId = 'pi_test123';

        $this->paymentEventQueryRepoMock
            ->shouldReceive('existsByPaymentIntentId')
            ->with($paymentIntentId, Mockery::type(\App\Core\Domain\Enum\Payment\PaymentEventType::class))
            ->once()
            ->andReturn(false);

        $this->userQueryRepoMock
            ->shouldReceive('findBySearch')
            ->with($search)
            ->once()
            ->andReturn(null);

        // Assert & Act
        $this->expectException(UserNotFoundException::class);

        $this->service->validateAndGetOrCreatePayment($search, $paymentIntentId);
    }

    #[Test]
    public function it_throws_exception_when_payment_already_validated(): void
    {
        // Arrange
        $search = 'S12345678';
        $paymentIntentId = 'pi_already_validated';

        $studentMock = Mockery::mock(User::class);

        $this->paymentEventQueryRepoMock
            ->shouldReceive('existsByPaymentIntentId')
            ->with($paymentIntentId, Mockery::type(\App\Core\Domain\Enum\Payment\PaymentEventType::class))
            ->once()
            ->andReturn(true);

        $this->userQueryRepoMock
            ->shouldReceive('findBySearch')
            ->with($search)
            ->once()
            ->andReturn($studentMock);

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Este pago ya fue validado antes');

        $this->service->validateAndGetOrCreatePayment($search, $paymentIntentId);
    }

    #[Test]
    public function it_creates_new_payment_when_user_and_payment_concept_exist(): void
    {
        // Arrange
        $search = 'S12345678';
        $paymentIntentId = 'pi_test123';

        // Mock del estudiante
        $studentMock = Mockery::mock(User::class);
        $studentMock->shouldReceive('getId')->andReturn(1);

        // Mock del concepto de pago
        $paymentConceptMock = Mockery::mock(PaymentConcept::class);
        $paymentConceptMock->shouldReceive('getAmount')->andReturn('5000.00');
        $paymentConceptMock->shouldReceive('getConceptName')->andReturn('Matrícula Semestral');

        // Mock del método de pago
        $paymentMethodMock = Mockery::mock(PaymentMethod::class);
        $paymentMethodMock->shouldReceive('getId')->andReturn(1);

        // Mock del pago creado
        $createdPaymentMock = Mockery::mock(Payment::class);
        $createdPaymentMock->shouldReceive('getId')->andReturn(1);
        $createdPaymentMock->shouldReceive('getAmount')->andReturn('5000.00');
        $createdPaymentMock->shouldReceive('getAmountReceived')->andReturn('5000.00');
        $createdPaymentMock->shouldReceive('getStatus')->andReturn(\App\Core\Domain\Enum\Payment\PaymentStatus::SUCCEEDED);
        $createdPaymentMock->shouldReceive('getPaymentConceptId')->andReturn(1);
        $createdPaymentMock->shouldReceive('getUserId')->andReturn(1);

        // Configurar mocks
        $this->paymentEventQueryRepoMock
            ->shouldReceive('existsByPaymentIntentId')
            ->with($paymentIntentId, Mockery::type(\App\Core\Domain\Enum\Payment\PaymentEventType::class))
            ->once()
            ->andReturn(false);

        $this->userQueryRepoMock
            ->shouldReceive('findBySearch')
            ->with($search)
            ->once()
            ->andReturn($studentMock);

        $this->paymentQueryRepoMock
            ->shouldReceive('findByIntentOrSession')
            ->with(1, $paymentIntentId)
            ->once()
            ->andReturn(null);

        // Mock de Stripe
        $stripeIntent = (object) [
            'id' => $paymentIntentId,
            'metadata' => (object) [
                'payment_concept_id' => 1
            ],
            'latest_charge' => (object) [
                'id' => 'ch_test123'
            ]
        ];

        $stripeCharge = (object) [
            'amount_received' => 500000, // 5000.00 en centavos
            'payment_method' => 'pm_test123',
            'payment_method_details' => (object) [
                'type' => 'card',
                'card' => (object) [
                    'brand' => 'visa',
                    'last4' => '4242'
                ]
            ],
            'receipt_url' => 'https://receipt.stripe.com/test'
        ];

        $this->stripeGatewayMock
            ->shouldReceive('getIntentAndCharge')
            ->with($paymentIntentId)
            ->once()
            ->andReturn([
                'intent' => $stripeIntent,
                'charge' => $stripeCharge
            ]);

        $this->paymentConceptQueryRepoMock
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($paymentConceptMock);

        $this->paymentMethodQueryRepoMock
            ->shouldReceive('findByStripeId')
            ->with('pm_test123')
            ->once()
            ->andReturn($paymentMethodMock);

        // Mock para countSessionsByMetadata y getSessionsByMetadata
        $this->stripeGatewayMock
            ->shouldReceive('countSessionsByMetadata')
            ->andReturn(1);

        $this->stripeGatewayMock
            ->shouldReceive('getSessionsByMetadata')
            ->andReturn([]);

        $this->paymentRepoMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::type(Payment::class))
            ->andReturn($createdPaymentMock);

        $this->paymentRepoMock
            ->shouldReceive('update')
            ->andReturn($createdPaymentMock);

        // Act
        [$payment, $student, $wasCreated, $wasReconciled, $reconcileResponse] =
            $this->service->validateAndGetOrCreatePayment($search, $paymentIntentId);

        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertInstanceOf(User::class, $student);
        $this->assertTrue($wasCreated);
        $this->assertFalse($wasReconciled);
        $this->assertNull($reconcileResponse);
    }

    #[Test]
    public function it_reconciles_existing_payment_when_found(): void
    {
        // Arrange
        $search = 'S12345678';
        $paymentIntentId = 'pi_existing123';

        // Mock del estudiante
        $studentMock = Mockery::mock(User::class);
        $studentMock->shouldReceive('getId')->andReturn(1);

        // Mock del pago existente
        $existingPaymentMock = Mockery::mock(Payment::class);
        $existingPaymentMock->shouldReceive('getId')->andReturn(1);
        $existingPaymentMock->shouldReceive('getAmount')->andReturn('5000.00');
        $existingPaymentMock->shouldReceive('getAmountReceived')->andReturn('4000.00');
        $existingPaymentMock->shouldReceive('getStatus')->andReturn(\App\Core\Domain\Enum\Payment\PaymentStatus::DEFAULT);
        $existingPaymentMock->shouldReceive('getPaymentConceptId')->andReturn(1);
        $existingPaymentMock->shouldReceive('getUserId')->andReturn(1);

        // Mock del resultado de reconciliación
        $reconciliationResult = new ReconciliationResult(
            processed: 1,
            updated: 1,
            notified: 1,
            failed: 0
        );

        // Configurar mocks
        $this->paymentEventQueryRepoMock
            ->shouldReceive('existsByPaymentIntentId')
            ->with($paymentIntentId, Mockery::type(\App\Core\Domain\Enum\Payment\PaymentEventType::class))
            ->once()
            ->andReturn(false);

        $this->userQueryRepoMock
            ->shouldReceive('findBySearch')
            ->with($search)
            ->once()
            ->andReturn($studentMock);

        $this->paymentQueryRepoMock
            ->shouldReceive('findByIntentOrSession')
            ->with(1, $paymentIntentId)
            ->once()
            ->andReturn($existingPaymentMock);

        $this->reconcileUseCaseMock
            ->shouldReceive('execute')
            ->with($existingPaymentMock)
            ->once()
            ->andReturn([$reconciliationResult, $existingPaymentMock, true]);

        // Mock para countSessionsByMetadata y getSessionsByMetadata
        $this->stripeGatewayMock
            ->shouldReceive('countSessionsByMetadata')
            ->andReturn(1);

        $this->stripeGatewayMock
            ->shouldReceive('getSessionsByMetadata')
            ->andReturn([]);

        $this->paymentRepoMock
            ->shouldReceive('update')
            ->andReturn($existingPaymentMock);

        // Act
        [$payment, $student, $wasCreated, $wasReconciled, $reconcileResponse] =
            $this->service->validateAndGetOrCreatePayment($search, $paymentIntentId);

        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertInstanceOf(User::class, $student);
        $this->assertFalse($wasCreated);
        $this->assertTrue($wasReconciled);
        $this->assertInstanceOf(ReconciliationResult::class, $reconcileResponse);
    }

}
