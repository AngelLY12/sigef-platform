<?php

namespace Tests\Unit\Infraestructure\Repositories\Stripe;

use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Infraestructure\Mappers\PaymentConceptMapper;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Core\Infraestructure\Repositories\Stripe\StripeGateway;
use App\Exceptions\ServerError\StripeGatewayException;
use App\Exceptions\Validation\PayoutValidationException;
use App\Exceptions\Validation\ValidationException;
use Stripe\PaymentMethod as StripePaymentMethod;
use App\Models\PaymentConcept as EloquentPaymentConcept;
use App\Models\User as EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use Stripe\Balance;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Payout;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeGatewayTest extends TestCase
{
    use RefreshDatabase;

    private StripeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_fake_key',
            'app.frontend_url' => 'https://frontend.test'
        ]);
        $this->gateway = new StripeGateway();

    }

    protected function tearDown(): void
    {
        $container = Mockery::getContainer();
        if ($container) {
            $container->mockery_close();
        }
        Mockery::close();
        parent::tearDown();
    }

    // ==================== CREATE STRIPE USER TESTS ====================

    #[Test]
    public function create_stripe_user_returns_existing_customer_id(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create([
            'stripe_customer_id' => 'cus_existing123'
        ]);

        $domainUser = UserMapper::toDomain($user);

        // Act
        $result = $this->gateway->createStripeUser($domainUser);

        // Assert
        $this->assertEquals('cus_existing123', $result);
    }

    #[Test]
    public function create_stripe_user_creates_new_customer(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create([
            'stripe_customer_id' => null,
            'email' => 'test@example.com',
            'name' => 'John',
            'last_name' => 'Doe'
        ]);

        $domainUser = UserMapper::toDomain($user);

        // Mock Customer usando overload UNA VEZ por test
        $customerMock = Mockery::mock('overload:Stripe\Customer');

        $allResult = new class {
            public $data = [];
        };

        $newCustomer = new class {
            public $id = 'cus_new123';
        };

        $customerMock->shouldReceive('all')
            ->with(['email' => 'test@example.com', 'limit' => 1])
            ->andReturn($allResult);

        $customerMock->shouldReceive('create')
            ->with([
                'email' => 'test@example.com',
                'name' => 'John Doe',
            ])
            ->andReturn($newCustomer);

        // Act
        $result = $this->gateway->createStripeUser($domainUser);

        // Assert
        $this->assertEquals('cus_new123', $result);
    }

    #[Test]
    public function create_stripe_user_returns_existing_customer_by_email(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create([
            'stripe_customer_id' => null,
            'email' => 'existing@example.com'
        ]);

        $domainUser = UserMapper::toDomain($user);

        // Mock Customer
        $customerMock = Mockery::mock('overload:Stripe\Customer');

        $existingCustomer = new class {
            public $id = 'cus_existing456';
        };

        $allResult = new class {
            public $data = [];
        };
        $allResult->data = [$existingCustomer];

        $customerMock->shouldReceive('all')
            ->with(['email' => 'existing@example.com', 'limit' => 1])
            ->andReturn($allResult);

        // Act
        $result = $this->gateway->createStripeUser($domainUser);

        // Assert
        $this->assertEquals('cus_existing456', $result);
    }

    // ==================== CREATE SETUP SESSION TESTS ====================


    /*
    #[Test]
    public function create_setup_session_successfully(): void
    {
        // Arrange
        $customerId = 'cus_test123';

        // Crear un mock que SE HAGA PASAR por Stripe\Checkout\Session
        // Usando 'alias:' para que PHP piense que es la clase real
        $expectedSession = Mockery::mock('overload:Stripe\Checkout\Session');

        // Mockear la clase estática con un nombre diferente para evitar conflictos
        $sessionMock = Mockery::mock('overload:Stripe\Checkout\Session');

        $sessionMock->shouldReceive('create')
            ->with([
                'mode' => 'setup',
                'payment_method_types' => ['card'],
                'customer' => $customerId,
                'success_url' => 'https://frontend.test/setup-success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://frontend.test/setup-cancel',
            ])
            ->andReturn($expectedSession);

        // Act
        $result = $this->gateway->createSetupSession($customerId);

        // Assert - Ahora es un Mock, no stdClass
        $this->assertInstanceOf(Mockery\MockInterface::class, $result);
        $this->assertEquals('cs_setup_123', $result->id);
    }

// ==================== CREATE CHECKOUT SESSION TESTS ====================

    #[Test]
    public function create_checkout_session_successfully(): void
    {
        // Arrange
        $customerId = 'cus_test123';

        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'concept_name' => 'Tuition Fee',
            'amount' => '1500.00'
        ]);

        $domainPaymentConcept = PaymentConceptMapper::toDomain($paymentConcept);
        $amount = '1500.00';

        // Crear mock que se haga pasar por Session
        $expectedSession = Mockery::mock('overload:Stripe\Checkout\Session');
        $expectedSession->id = 'cs_checkout_123';
        $expectedSession->url = 'https://checkout.stripe.com/pay/test';

        $sessionMock = Mockery::mock('overload:Stripe\Checkout\Session');

        $sessionMock->shouldReceive('create')
            ->with(Mockery::on(function($data) use ($customerId, $paymentConcept) {
                return $data['customer'] === $customerId &&
                    $data['metadata']['payment_concept_id'] == $paymentConcept->id &&
                    $data['line_items'][0]['price_data']['unit_amount'] === 150000;
            }))
            ->andReturn($expectedSession);

        // Act
        $result = $this->gateway->createCheckoutSession($customerId, $domainPaymentConcept, $amount);

        // Assert
        $this->assertInstanceOf(Mockery\MockInterface::class, $result);
        $this->assertEquals('cs_checkout_123', $result->id);
    }

    */

    // ==================== DELETE PAYMENT METHOD TESTS ====================

    #[Test]
    public function delete_payment_method_successfully(): void
    {
        // Arrange
        $paymentMethodId = 'pm_123456';

        // Mock PaymentMethod
        $paymentMethodMock = Mockery::mock('overload:Stripe\PaymentMethod');

        $pmInstance = new class {
            public function detach() {}
        };

        $paymentMethodMock->shouldReceive('retrieve')
            ->with($paymentMethodId)
            ->andReturn($pmInstance);

        // Act
        $result = $this->gateway->deletePaymentMethod($paymentMethodId);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function delete_payment_method_throws_exception_for_invalid_id(): void
    {
        // Arrange
        $invalidId = 'invalid_id';

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El ID de método de pago tiene un formato inválido');

        // Act
        $this->gateway->deletePaymentMethod($invalidId);
    }

    // ==================== EXPIRE SESSION TESTS ====================

    #[Test]
    public function expire_session_if_pending_expires_successfully(): void
    {
        // Arrange
        $sessionId = 'cs_test_123';

        // Mock Session
        $sessionMock = Mockery::mock('overload:Stripe\Checkout\Session');

        $sessionInstance = new class {
            public $status = 'open';
            public $payment_status = '';
            public $created = 0;
            public $payment_intent = null;

            public function expire() {}
        };
        $sessionInstance->payment_status = PaymentStatus::UNPAID->value;
        $sessionInstance->created = time();

        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andReturn($sessionInstance);

        // Act
        $result = $this->gateway->expireSessionIfPending($sessionId);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function expire_session_if_pending_returns_false_for_expired_session(): void
    {
        // Arrange
        $sessionId = 'cs_test_123';

        // Mock Session
        $sessionMock = Mockery::mock('overload:Stripe\Checkout\Session');

        $sessionInstance = new class {
            public $status = 'expired';
        };

        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andReturn($sessionInstance);

        // Act
        $result = $this->gateway->expireSessionIfPending($sessionId);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function expire_session_if_pending_returns_false_for_old_session(): void
    {
        // Arrange
        $sessionId = 'cs_test_123';

        // Mock Session
        $sessionMock = Mockery::mock('overload:Stripe\Checkout\Session');

        $sessionInstance = new class {
            public $status = 'open';
            public $payment_status = '';
            public $created = 0;
            public $payment_intent = null;
        };
        $sessionInstance->payment_status = PaymentStatus::UNPAID->value;
        $sessionInstance->created = time() - 4000;

        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andReturn($sessionInstance);

        // Act
        $result = $this->gateway->expireSessionIfPending($sessionId);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function expire_session_if_pending_throws_exception_for_invalid_session_id(): void
    {
        // Arrange
        $invalidId = 'invalid_session_id';

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El ID de ID de la sesión tiene un formato inválido');

        // Act
        $this->gateway->expireSessionIfPending($invalidId);
    }

    // ==================== CREATE PAYOUT TESTS ====================

    #[Test]
    public function create_payout_successfully(): void
    {
        // Arrange
        $balanceAmount = 1500000;

        // Mock Balance
        $balanceMock = Mockery::mock('overload:Stripe\Balance');

        $balanceInstance = new class {
            public $available = [];
        };
        $balanceInstance->available = [
            (object) ['currency' => 'mxn', 'amount' => $balanceAmount],
            (object) ['currency' => 'usd', 'amount' => 1000]
        ];

        $balanceMock->shouldReceive('retrieve')
            ->andReturn($balanceInstance);

        // Mock Payout
        $payoutMock = Mockery::mock('overload:Stripe\Payout');

        $payoutInstance = new class {
            public $id = 'po_123456';
            public $amount = 0;
            public $currency = '';
            public $arrival_date = 0;
            public $status = '';
        };
        $payoutInstance->amount = $balanceAmount;
        $payoutInstance->currency = 'mxn';
        $payoutInstance->arrival_date = strtotime('+3 days');
        $payoutInstance->status = 'in_transit';

        $payoutMock->shouldReceive('create')
            ->with([
                'amount' => intval($balanceAmount),
                'currency' => 'mxn',
                'description' => 'Payout manual de la escuela',
            ])
            ->andReturn($payoutInstance);

        // Act
        $result = $this->gateway->createPayout();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('po_123456', $result['payout_id']);
        $this->assertEquals('15000.00', $result['amount']);
        $this->assertEquals('mxn', $result['currency']);
        $this->assertEquals('15000.00', $result['available_before_payout']);
    }

    #[Test]
    public function create_payout_throws_exception_for_insufficient_funds(): void
    {
        // Arrange
        $balanceAmount = 5000;

        // Mock Balance
        $balanceMock = Mockery::mock('overload:Stripe\Balance');

        $balanceInstance = new class {
            public $available = [];
        };
        $balanceInstance->available = [
            (object) ['currency' => 'mxn', 'amount' => $balanceAmount]
        ];

        $balanceMock->shouldReceive('retrieve')
            ->andReturn($balanceInstance);

        // Assert - Corrección: El mensaje exacto
        $this->expectException(StripeGatewayException::class);
        $this->expectExceptionMessage('Error inesperado: Fondos insuficientes. Disponible: $50.00 MXN. Mínimo requerido: $100.00 MXN');

        // Act
        $this->gateway->createPayout();
    }

    #[Test]
    public function create_payout_handles_stripe_api_error(): void
    {
        // Arrange
        $balanceMock = Mockery::mock('overload:Stripe\Balance');

        $balanceInstance = new class {
            public $available = [];
        };
        $balanceInstance->available = [
            (object) ['currency' => 'mxn', 'amount' => 1500000]
        ];

        $balanceMock->shouldReceive('retrieve')
            ->andReturn($balanceInstance);

        // Mock Payout para lanzar excepción
        $payoutMock = Mockery::mock('overload:Stripe\Payout');

        // Crear una excepción simple
        $exception = new \Exception('Stripe API error');

        $payoutMock->shouldReceive('create')
            ->andThrow($exception);

        // Assert
        $this->expectException(StripeGatewayException::class);
        $this->expectExceptionMessage('Error inesperado: Stripe API error');

        // Act
        $this->gateway->createPayout();
    }

    // ==================== ERROR HANDLING TESTS ====================

    #[Test]
    public function create_stripe_user_handles_stripe_api_error(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create([
            'email' => 'error@example.com',
            'stripe_customer_id' => null
        ]);

        $domainUser = UserMapper::toDomain($user);

        // Mock Customer - usar alias en lugar de overload
        $customerMock = Mockery::mock('alias:Stripe\Customer');

        // IMPORTANTE: Tu código captura ApiErrorException específicamente
        // Mirando tu implementación: }catch(ApiErrorException $e){
        // Entonces DEBES lanzar ApiErrorException, no Exception
        $apiErrorException = Mockery::mock('Stripe\Exception\ApiErrorException');
        $apiErrorException->shouldReceive('getMessage')->andReturn('Stripe API error');

        $customerMock->shouldReceive('all')
            ->andThrow($apiErrorException);

        // Assert
        $this->expectException(StripeGatewayException::class);
        $this->expectExceptionMessage('Error al crear el cliente en Stripe');

        // Act
        $this->gateway->createStripeUser($domainUser);
    }

    #[Test]
    public function create_checkout_session_handles_rate_limit(): void
    {
        // Arrange
        $customerId = 'cus_test';
        $userId = 123;

        $paymentConcept = EloquentPaymentConcept::factory()->create();
        $domainPaymentConcept = PaymentConceptMapper::toDomain($paymentConcept);
        $amount = '1500.00';

        // Mock Session
        $sessionMock = Mockery::mock('alias:Stripe\Checkout\Session');

        $rateLimitException = new \Stripe\Exception\RateLimitException('Rate limit exceeded');

        $sessionMock->shouldReceive('create')
            ->andThrow($rateLimitException);

        // Assert
        $this->expectException(StripeGatewayException::class);

        // Act
        $this->gateway->createCheckoutSession($customerId, $domainPaymentConcept, $amount, $userId);
    }

    // ==================== VALIDATION TESTS ====================

    #[Test]
    public function create_stripe_user_validates_user_data(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create(['email' => '']);
        $domainUser = UserMapper::toDomain($user);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El usuario no tiene correo electrónico');

        // Act
        $this->gateway->createStripeUser($domainUser);
    }

    // ==================== INTEGRATION SCENARIOS ====================

    #[Test]
    public function complete_user_registration_and_payment_flow(): void
    {
        // 1. Crear usuario
        $user = EloquentUser::factory()->create([
            'email' => 'integration@example.com',
            'stripe_customer_id' => null
        ]);
        $domainUser = UserMapper::toDomain($user);

        // Mock para createStripeUser
        $customerMock = Mockery::mock('overload:Stripe\Customer');

        $allResult = new class {
            public $data = [];
        };

        $newCustomer = new class {
            public $id = 'cus_integration_123';
        };

        $customerMock->shouldReceive('all')
            ->with(['email' => 'integration@example.com', 'limit' => 1])
            ->andReturn($allResult);
        $customerMock->shouldReceive('create')
            ->andReturn($newCustomer);

        // 2. Crear usuario en Stripe
        $customerId = $this->gateway->createStripeUser($domainUser);

        // Assert
        $this->assertEquals('cus_integration_123', $customerId);
    }

    // ==================== EDGE CASES ====================

    #[Test]
    public function create_payout_with_exact_minimum_amount(): void
    {
        // Arrange
        $balanceAmount = 10000;

        // Mock Balance
        $balanceMock = Mockery::mock('overload:Stripe\Balance');

        $balanceInstance = new class {
            public $available = [];
        };
        $balanceInstance->available = [
            (object) ['currency' => 'mxn', 'amount' => $balanceAmount]
        ];

        $balanceMock->shouldReceive('retrieve')
            ->andReturn($balanceInstance);

        // Mock Payout
        $payoutMock = Mockery::mock('overload:Stripe\Payout');

        $payoutInstance = new class {
            public $id = 'po_min_123';
            public $amount = 0;
            public $currency = '';
            public $arrival_date = 0;
            public $status = '';
        };
        $payoutInstance->amount = $balanceAmount;
        $payoutInstance->currency = 'mxn';
        $payoutInstance->arrival_date = strtotime('+3 days');
        $payoutInstance->status = 'in_transit';

        $payoutMock->shouldReceive('create')
            ->with([
                'amount' => $balanceAmount,
                'currency' => 'mxn',
                'description' => 'Payout manual de la escuela',
            ])
            ->andReturn($payoutInstance);

        // Act
        $result = $this->gateway->createPayout();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('100.00', $result['amount']);
    }

    #[Test]
    public function expire_session_with_payment_intent_cancellation(): void
    {
        // Arrange
        $sessionId = 'cs_test_456';

        // Mock Session
        $sessionMock = Mockery::mock('overload:Stripe\Checkout\Session');

        $sessionInstance = new class {
            public $status = 'open';
            public $payment_status = '';
            public $created = 0;
            public $payment_intent = 'pi_123456';

            public function expire() {}
        };
        $sessionInstance->payment_status = PaymentStatus::REQUIRES_ACTION->value;
        $sessionInstance->created = time();

        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andReturn($sessionInstance);

        // Mock PaymentIntent
        $paymentIntentMock = Mockery::mock('overload:Stripe\PaymentIntent');

        $piInstance = new class {
            public $status = 'requires_action';

            public function cancel($params) {}
        };

        $paymentIntentMock->shouldReceive('retrieve')
            ->with('pi_123456')
            ->andReturn($piInstance);

        // Act
        $result = $this->gateway->expireSessionIfPending($sessionId);

        // Assert
        $this->assertTrue($result);
    }
    private function createSessionMock()
    {
        // Limpiar cualquier mock previo
        Mockery::close();

        // Crear nuevo mock
        $container = Mockery::getContainer();
        if ($container) {
            $container->mockery_close();
        }

        return Mockery::mock('alias:Stripe\Checkout\Session');
    }
}
