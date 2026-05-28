<?php

namespace Tests\Unit\Infraestructure\Repositories\Stripe;

use App\Core\Infraestructure\Repositories\Stripe\StripeGatewayQuery;
use App\Exceptions\ServerError\StripeGatewayException;
use App\Exceptions\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Stripe\PaymentIntent;
use Tests\TestCase;
use Mockery;

class StripeGatewayQueryTest extends TestCase
{
    use RefreshDatabase;

    private StripeGatewayQuery $gatewayQuery;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_fake_key',
        ]);

        $this->gatewayQuery = new StripeGatewayQuery();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== GET SETUP INTENT FROM SESSION TESTS ====================

    #[Test]
    public function get_setup_intent_from_session_returns_setup_intent(): void
    {
        // Arrange
        $sessionId = 'cs_test_123';
        $setupIntentId = 'seti_test_456';

        // Mock Session
        $sessionMock = Mockery::mock('alias:Stripe\Checkout\Session');
        $session = new \stdClass();
        $session->setup_intent = $setupIntentId;
        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andReturn($session);

        // Mock SetupIntent
        $setupIntentMock = Mockery::mock('alias:Stripe\SetupIntent');
        $setupIntent = new \stdClass();
        $setupIntentMock->shouldReceive('retrieve')
            ->with($setupIntentId)
            ->andReturn($setupIntent);

        // Act
        $result = $this->gatewayQuery->getSetupIntentFromSession($sessionId);

        // Assert
        $this->assertSame($setupIntent, $result);
    }

    #[Test]
    public function get_setup_intent_from_session_returns_null_when_no_setup_intent(): void
    {
        // Arrange
        $sessionId = 'cs_test_123';

        // Mock Session
        $sessionMock = Mockery::mock('alias:Stripe\Checkout\Session');
        $session = new \stdClass();
        $session->setup_intent = null;
        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andReturn($session);

        // Act
        $result = $this->gatewayQuery->getSetupIntentFromSession($sessionId);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function get_setup_intent_from_session_throws_stripe_gateway_exception_on_api_error(): void
    {
        // Arrange
        $sessionId = 'cs_test_123';

        // Mock Session para lanzar excepción
        $sessionMock = Mockery::mock('alias:Stripe\Checkout\Session');
        $exception = Mockery::mock('Stripe\Exception\ApiErrorException');
        $exception->shouldReceive('getMessage')->andReturn('Stripe API error');

        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andThrow($exception);

        // Assert
        $this->expectException(StripeGatewayException::class);
        $this->expectExceptionMessage('Error trayendo el intent de la sesión');

        // Act
        $this->gatewayQuery->getSetupIntentFromSession($sessionId);
    }

    // ==================== RETRIEVE PAYMENT METHOD TESTS ====================

    #[Test]
    public function retrieve_payment_method_successfully(): void
    {
        // Arrange
        $paymentMethodId = 'pm_123456';

        // Mock PaymentMethod
        $paymentMethodMock = Mockery::mock('alias:Stripe\PaymentMethod');
        $paymentMethod = new \stdClass();
        $paymentMethodMock->shouldReceive('retrieve')
            ->with($paymentMethodId)
            ->andReturn($paymentMethod);

        // Act
        $result = $this->gatewayQuery->retrievePaymentMethod($paymentMethodId);

        // Assert
        $this->assertSame($paymentMethod, $result);
    }

    #[Test]
    public function retrieve_payment_method_throws_stripe_gateway_exception_on_api_error(): void
    {
        // Arrange
        $paymentMethodId = 'pm_123456';

        // Mock PaymentMethod para lanzar excepción
        $paymentMethodMock = Mockery::mock('alias:Stripe\PaymentMethod');
        $exception = Mockery::mock('Stripe\Exception\ApiErrorException');
        $exception->shouldReceive('getMessage')->andReturn('Stripe API error');

        $paymentMethodMock->shouldReceive('retrieve')
            ->with($paymentMethodId)
            ->andThrow($exception);

        // Assert
        $this->expectException(StripeGatewayException::class);
        $this->expectExceptionMessage('Error obteniendo el método de pago');

        // Act
        $this->gatewayQuery->retrievePaymentMethod($paymentMethodId);
    }

    // ==================== GET INTENT AND CHARGE TESTS ====================

    #[Test]
    public function get_intent_and_charge_successfully(): void
    {
        // Arrange
        $paymentIntentId = 'pi_123456';
        $chargeId = 'ch_123456';

        // Mock PaymentIntent
        $paymentIntentMock = Mockery::mock('alias:Stripe\PaymentIntent');
        $paymentIntent = new \stdClass();
        $paymentIntent->status = 'succeeded';
        $paymentIntent->charges = new \stdClass();
        $paymentIntent->charges->data = [new \stdClass()];
        $paymentIntent->charges->data[0]->id = $chargeId;
        $paymentIntent->latest_charge = null;

        $paymentIntentMock->shouldReceive('retrieve')
            ->with($paymentIntentId, ['expand' => ['charges', 'latest_charge']])
            ->andReturn($paymentIntent);

        // Act
        [$intentResult, $chargeResult] = $this->gatewayQuery->getIntentAndCharge($paymentIntentId);

        // Assert
        $this->assertSame($paymentIntent, $intentResult);
        $this->assertSame($paymentIntent->charges->data[0], $chargeResult);
    }

    #[Test]
    public function get_intent_and_charge_with_latest_charge(): void
    {
        // Arrange
        $paymentIntentId = 'pi_123456';
        $chargeId = 'ch_123456';

        // Mock PaymentIntent
        $paymentIntentMock = Mockery::mock('alias:Stripe\PaymentIntent');
        $paymentIntent = new \stdClass();
        $paymentIntent->status = 'succeeded';
        $paymentIntent->charges = new \stdClass();
        $paymentIntent->charges->data = [];
        $paymentIntent->latest_charge = $chargeId;

        // Mock Charge
        $chargeMock = Mockery::mock('alias:Stripe\Charge');
        $charge = new \stdClass();
        $charge->id = $chargeId;

        $paymentIntentMock->shouldReceive('retrieve')
            ->with($paymentIntentId, ['expand' => ['charges', 'latest_charge']])
            ->andReturn($paymentIntent);

        $chargeMock->shouldReceive('retrieve')
            ->with($chargeId)
            ->andReturn($charge);

        // Act
        [$intentResult, $chargeResult] = $this->gatewayQuery->getIntentAndCharge($paymentIntentId);

        // Assert
        $this->assertSame($paymentIntent, $intentResult);
        $this->assertSame($charge, $chargeResult);
    }

    #[Test]
    public function get_intent_and_charge_throws_validation_exception_when_intent_not_found(): void
    {
        // Arrange
        $paymentIntentId = 'pi_123456';

        // Mock PaymentIntent para devolver null
        $paymentIntentMock = Mockery::mock('alias:Stripe\PaymentIntent');
        $paymentIntentMock->shouldReceive('retrieve')
            ->with($paymentIntentId, ['expand' => ['charges', 'latest_charge']])
            ->andReturn(null);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Intent no encontrado en Stripe: {$paymentIntentId}");

        // Act
        $this->gatewayQuery->getIntentAndCharge($paymentIntentId);
    }

    #[Test]
    public function get_intent_and_charge_throws_stripe_gateway_exception_on_api_error(): void
    {
        // Arrange
        $paymentIntentId = 'pi_123456';

        // Mock PaymentIntent para lanzar excepción
        $paymentIntentMock = Mockery::mock('alias:Stripe\PaymentIntent');
        $exception = Mockery::mock('Stripe\Exception\ApiErrorException');
        $exception->shouldReceive('getMessage')->andReturn('Stripe API error');

        $paymentIntentMock->shouldReceive('retrieve')
            ->with($paymentIntentId, ['expand' => ['charges', 'latest_charge']])
            ->andThrow($exception);

        // Assert
        $this->expectException(StripeGatewayException::class);
        $this->expectExceptionMessage('Error obteniendo los datos');

        // Act
        $this->gatewayQuery->getIntentAndCharge($paymentIntentId);
    }

    // ==================== GET PAYMENT INTENT FROM SESSION TESTS ====================

    #[Test]
    public function get_payment_intent_from_session_successfully(): void
    {
        // Arrange
        $sessionId = 'cs_test_123';
        $paymentIntentId = 'pi_test_456';

        // Mock Session
        $sessionMock = Mockery::mock('alias:Stripe\Checkout\Session');
        $session = new \stdClass();
        $session->payment_intent = $paymentIntentId;
        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andReturn($session);

        // Mock PaymentIntent
        $paymentIntentMock = Mockery::mock('alias:Stripe\PaymentIntent');
        $paymentIntent = new PaymentIntent();
        $paymentIntentMock->shouldReceive('retrieve')
            ->with($paymentIntentId)
            ->andReturn($paymentIntent);

        // Act
        $result = $this->gatewayQuery->getPaymentIntentFromSession($sessionId);

        // Assert
        $this->assertSame($paymentIntent, $result);
    }

    #[Test]
    public function get_payment_intent_from_session_throws_validation_exception_when_no_payment_intent(): void
    {
        // Arrange
        $sessionId = 'cs_test_123';

        // Mock Session
        $sessionMock = Mockery::mock('alias:Stripe\Checkout\Session');
        $session = new \stdClass();
        $session->payment_intent = null;
        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andReturn($session);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Session sin payment_intent: {$sessionId}");

        // Act
        $this->gatewayQuery->getPaymentIntentFromSession($sessionId);
    }

    #[Test]
    public function get_payment_intent_from_session_throws_stripe_gateway_exception_on_api_error(): void
    {
        // Arrange
        $sessionId = 'cs_test_123';

        // Mock Session para lanzar excepción
        $sessionMock = Mockery::mock('alias:Stripe\Checkout\Session');
        $exception = Mockery::mock('Stripe\Exception\ApiErrorException');
        $exception->shouldReceive('getMessage')->andReturn('Stripe API error');

        $sessionMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->andThrow($exception);

        // Assert
        $this->expectException(StripeGatewayException::class);
        $this->expectExceptionMessage('Error obteniendo los datos');

        // Act
        $this->gatewayQuery->getPaymentIntentFromSession($sessionId);
    }

    // ==================== GET BALANCE FROM STRIPE TESTS ====================

    #[Test]
    public function get_balance_from_stripe_successfully(): void
    {
        // Arrange
        $balanceMock = Mockery::mock('alias:Stripe\Balance');
        $balance = new \stdClass();
        $balance->available = [
            (object)['amount' => 10000, 'source_types' => (object)['card' => 10000]],
            (object)['amount' => 5000, 'source_types' => (object)['bank_transfer' => 5000]]
        ];
        $balance->pending = [
            (object)['amount' => 2000, 'source_types' => (object)['card' => 2000]]
        ];

        $balanceMock->shouldReceive('retrieve')
            ->andReturn($balance);

        // Act
        $result = $this->gatewayQuery->getBalanceFromStripe();

        // Assert
        $this->assertEquals([
            'available' => [
                ['amount' => '100.00', 'source_types' => (object)['card' => 10000]],
                ['amount' => '50.00', 'source_types' => (object)['bank_transfer' => 5000]]
            ],
            'pending' => [
                ['amount' => '20.00', 'source_types' => (object)['card' => 2000]]
            ]
        ], $result);
    }

    // ==================== GET PAYOUTS FROM STRIPE TESTS ====================

    #[Test]
    public function get_payouts_from_stripe_with_only_this_year(): void
    {
        // Arrange
        $currentYear = date('Y');
        $payout1 = new \stdClass();
        $payout1->id = 'po_1';
        $payout1->amount = 5000; // 50.00
        $payout1->arrival_date = strtotime("$currentYear-06-15");
        $payout1->balance_transaction = (object)['fee' => 500]; // 5.00

        $payout2 = new \stdClass();
        $payout2->id = 'po_2';
        $payout2->amount = 3000; // 30.00
        $payout2->arrival_date = strtotime("$currentYear-07-20");
        $payout2->balance_transaction = (object)['fee' => 300]; // 3.00

        $payoutsPage = new \stdClass();
        $payoutsPage->data = [$payout1, $payout2];
        $payoutsPage->has_more = false;

        $payoutMock = Mockery::mock('alias:Stripe\Payout');
        $payoutMock->shouldReceive('all')
            ->with([
                'limit' => 100,
                'expand' => ['data.balance_transaction'],
                'created' => [
                    'gte' => strtotime("$currentYear-01-01"),
                    'lte' => strtotime("$currentYear-12-31 23:59:59")
                ]
            ])
            ->andReturn($payoutsPage);

        // Act
        $result = $this->gatewayQuery->getPayoutsFromStripe(true);

        // Assert
        $this->assertEquals('80.00', $result['total']); // 50 + 30
        $this->assertEquals('8.00', $result['total_fee']); // 5 + 3
        $this->assertArrayHasKey("$currentYear-06", $result['by_month']);
        $this->assertEquals('50.00', $result['by_month']["$currentYear-06"]['amount']);
        $this->assertEquals('5.00', $result['by_month']["$currentYear-06"]['fee']);
        $this->assertArrayHasKey("$currentYear-07", $result['by_month']);
        $this->assertEquals('30.00', $result['by_month']["$currentYear-07"]['amount']);
        $this->assertEquals('3.00', $result['by_month']["$currentYear-07"]['fee']);
    }

    // ==================== GET INTENTS AND CHARGES BATCH TESTS ====================

    #[Test]
    public function get_intents_and_charges_batch_returns_empty_array_for_empty_input(): void
    {
        // Act
        $result = $this->gatewayQuery->getIntentsAndChargesBatch([]);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_intents_and_charges_batch_successfully(): void
    {
        // Arrange
        $paymentIntentIds = ['pi_1', 'pi_2'];
        // Crear un solo mock
        $paymentIntentMock = Mockery::mock('alias:Stripe\PaymentIntent');

        // Crear intents
        $intent1 = $this->createMockIntent('pi_1', 'ch_1');
        $intent2 = $this->createMockIntent('pi_2', 'ch_2');

        // Configurar respuestas por defecto
        $paymentIntentMock->shouldReceive('retrieve')
            ->with('pi_1', ['expand' => ['charges', 'latest_charge']])
            ->andReturn($intent1)
            ->byDefault();

        $paymentIntentMock->shouldReceive('retrieve')
            ->with('pi_2', ['expand' => ['charges', 'latest_charge']])
            ->andReturn($intent2)
            ->byDefault();

        // Act
        $result = $this->gatewayQuery->getIntentsAndChargesBatch($paymentIntentIds);

        // Assert
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('pi_1', $result);
        $this->assertArrayHasKey('pi_2', $result);
    }

    private function createMockIntent(string $intentId, string $chargeId): \stdClass
    {
        $intent = new \stdClass();
        $intent->id = $intentId;
        $intent->status = 'succeeded';
        $intent->charges = new \stdClass();

        $charge = new \stdClass();
        $charge->id = $chargeId;
        $charge->amount = 1000;
        $charge->currency = 'usd';

        $intent->charges->data = [$charge];
        $intent->latest_charge = null;

        return $intent;
    }

    // ==================== VALIDATION TESTS ====================

    #[Test]
    public function get_setup_intent_from_session_throws_validation_exception_for_invalid_session_id(): void
    {
        // Arrange
        $invalidId = 'invalid_session_id';

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El ID de ID de la sesión tiene un formato inválido');

        // Act
        $this->gatewayQuery->getSetupIntentFromSession($invalidId);
    }

    #[Test]
    public function retrieve_payment_method_throws_validation_exception_for_invalid_id(): void
    {
        // Arrange
        $invalidId = 'invalid_pm_id';

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El ID de método de pago tiene un formato inválido');

        // Act
        $this->gatewayQuery->retrievePaymentMethod($invalidId);
    }

    #[Test]
    public function get_intent_and_charge_throws_validation_exception_for_invalid_payment_intent_id(): void
    {
        // Arrange
        $invalidId = 'invalid_pi_id';

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El ID de payment intent tiene un formato inválido');

        // Act
        $this->gatewayQuery->getIntentAndCharge($invalidId);
    }

    #[Test]
    public function count_sessions_by_metadata_successfully(): void
    {
        // Arrange
        $metadata = [
            'user_id' => 'user_123',
            'concept_id' => 'concept_456',
        ];
        $status = 'complete';
        $expectedTotalCount = 5;

        // Mock de Session::search
        $searchResultMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $searchResult = new \stdClass();
        $searchResult->total_count = $expectedTotalCount;

        $searchResultMock->shouldReceive('search')
            ->with([
                'query' => "metadata['user_id']:'user_123' AND metadata['concept_id']:'concept_456' AND status:'complete'",
            ])
            ->andReturn($searchResult);

        // Act
        $result = $this->gatewayQuery->countSessionsByMetadata($metadata, $status);

        // Assert
        $this->assertEquals($expectedTotalCount, $result);
    }

    #[Test]
    public function count_sessions_by_metadata_with_empty_metadata(): void
    {
        // Arrange
        $metadata = [];
        $status = 'complete';
        $expectedTotalCount = 3;

        // Mock de Session::search
        $searchResultMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $searchResult = new \stdClass();
        $searchResult->total_count = $expectedTotalCount;

        $searchResultMock->shouldReceive('search')
            ->with([
                'query' => "status:'complete'",
            ])
            ->andReturn($searchResult);

        // Act
        $result = $this->gatewayQuery->countSessionsByMetadata($metadata, $status);

        // Assert
        $this->assertEquals($expectedTotalCount, $result);
    }

    #[Test]
    public function count_sessions_by_metadata_returns_zero_on_stripe_api_error(): void
    {
        // Arrange
        $metadata = ['user_id' => 'user_123'];
        $status = 'complete';

        // Mock de Session::search para lanzar excepción
        $searchResultMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $exception = new \Exception('Stripe API error');

        $searchResultMock->shouldReceive('search')
            ->with([
                'query' => "metadata['user_id']:'user_123' AND status:'complete'",
            ])
            ->andThrow($exception);

        // Mock del logger
        $loggerMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $loggerMock->shouldReceive('error')
            ->withArgs(function ($message) {
                return str_contains($message, 'Error contando sesiones por estado');
            });

        // Act
        $result = $this->gatewayQuery->countSessionsByMetadata($metadata, $status);

        // Assert
        $this->assertEquals(0, $result);
    }

    // ==================== GET SESSIONS BY METADATA TESTS ====================

    #[Test]
    public function get_sessions_by_metadata_successfully(): void
    {
        // Arrange
        $metadataFilters = [
            'user_id' => 'user_123',
            'concept_id' => 'concept_456',
        ];
        $status = 'complete';
        $limit = 50;

        // Mock de datos de Stripe
        $stripeSession1 = new \stdClass();
        $stripeSession1->id = 'cs_test_123';
        $stripeSession1->amount_total = 10000; // $100.00 en centavos
        $stripeSession1->payment_status = 'paid';
        $stripeSession1->metadata = (object)['user_id' => 'user_123', 'concept_id' => 'concept_456'];
        $stripeSession1->created = 1678901234;
        $stripeSession1->customer = 'cus_123';

        $paymentIntent1 = new \stdClass();
        $paymentIntent1->id = 'pi_123';
        $paymentIntent1->amount_received = 10000;
        $stripeSession1->payment_intent = $paymentIntent1;

        $stripeSession2 = new \stdClass();
        $stripeSession2->id = 'cs_test_456';
        $stripeSession2->amount_total = 5000; // $50.00 en centavos
        $stripeSession2->payment_status = 'paid';
        $stripeSession2->metadata = (object)['user_id' => 'user_123', 'concept_id' => 'concept_456'];
        $stripeSession2->created = 1678912345;
        $stripeSession2->customer = 'cus_456';
        $stripeSession2->payment_intent = null;

        $searchResult = new \stdClass();
        $searchResult->data = [$stripeSession1, $stripeSession2];

        // Mock de Session::search
        $searchMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $searchMock->shouldReceive('search')
            ->with([
                'query' => "metadata['user_id']:'user_123' AND metadata['concept_id']:'concept_456' AND status:'complete'",
                'limit' => $limit,
                'expand' => 'data.payment_intent'
            ])
            ->andReturn($searchResult);

        // Mock del logger para debug
        $loggerMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $loggerMock->shouldReceive('debug')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Encontradas')
                    && isset($context['filters'])
                    && isset($context['session_ids']);
            });

        // Act
        $result = $this->gatewayQuery->getSessionsByMetadata($metadataFilters, $status, $limit);

        // Assert
        $this->assertCount(2, $result);

        // Verificar primera sesión
        $this->assertEquals('cs_test_123', $result[0]['id']);
        $this->assertEquals('pi_123', $result[0]['payment_intent_id']);
        $this->assertEquals(10000, $result[0]['amount_total']);
        $this->assertEquals(10000, $result[0]['amount_received']);
        $this->assertEquals('paid', $result[0]['status']);
        $this->assertEquals(['user_id' => 'user_123', 'concept_id' => 'concept_456'], $result[0]['metadata']);
        $this->assertEquals(1678901234, $result[0]['created']);
        $this->assertEquals('cus_123', $result[0]['customer']);

        // Verificar segunda sesión (sin payment_intent)
        $this->assertEquals('cs_test_456', $result[1]['id']);
        $this->assertNull($result[1]['payment_intent_id']);
        $this->assertEquals(5000, $result[1]['amount_total']);
        $this->assertNull($result[1]['amount_received']);
        $this->assertEquals('paid', $result[1]['status']);
    }

    #[Test]
    public function get_sessions_by_metadata_with_default_limit(): void
    {
        // Arrange
        $metadataFilters = ['user_id' => 'user_123'];
        $status = 'complete';
        $expectedLimit = 100; // Valor por defecto

        $searchResult = new \stdClass();
        $searchResult->data = [];

        // Mock de Session::search
        $searchMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $searchMock->shouldReceive('search')
            ->with([
                'query' => "metadata['user_id']:'user_123' AND status:'complete'",
                'limit' => $expectedLimit,
                'expand' => 'data.payment_intent'
            ])
            ->andReturn($searchResult);

        // Mock del logger
        $loggerMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $loggerMock->shouldReceive('debug');

        // Act
        $result = $this->gatewayQuery->getSessionsByMetadata($metadataFilters, $status);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_sessions_by_metadata_with_special_characters_in_metadata(): void
    {
        // Arrange
        $metadataFilters = [
            'user_email' => 'test@example.com',
            'reference' => 'REF-123/456',
        ];
        $status = 'complete';

        $searchResult = new \stdClass();
        $searchResult->data = [];

        // Mock de Session::search
        $searchMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $searchMock->shouldReceive('search')
            ->with([
                'query' => "metadata['user_email']:'test@example.com' AND metadata['reference']:'REF-123/456' AND status:'complete'",
                'limit' => 100,
                'expand' => 'data.payment_intent'
            ])
            ->andReturn($searchResult);

        // Mock del logger
        $loggerMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $loggerMock->shouldReceive('debug');

        // Act
        $result = $this->gatewayQuery->getSessionsByMetadata($metadataFilters, $status);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_sessions_by_metadata_handles_null_payment_intent(): void
    {
        // Arrange
        $metadataFilters = ['user_id' => 'user_123'];
        $status = 'complete';

        // Mock de sesión sin payment_intent
        $stripeSession = new \stdClass();
        $stripeSession->id = 'cs_test_123';
        $stripeSession->amount_total = 10000;
        $stripeSession->payment_status = 'paid';
        $stripeSession->metadata = (object)['user_id' => 'user_123'];
        $stripeSession->created = 1678901234;
        $stripeSession->customer = 'cus_123';
        $stripeSession->payment_intent = null; // Sin payment intent

        $searchResult = new \stdClass();
        $searchResult->data = [$stripeSession];

        // Mock de Session::search
        $searchMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $searchMock->shouldReceive('search')
            ->andReturn($searchResult);

        // Mock del logger
        $loggerMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $loggerMock->shouldReceive('debug');

        // Act
        $result = $this->gatewayQuery->getSessionsByMetadata($metadataFilters, $status);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('cs_test_123', $result[0]['id']);
        $this->assertNull($result[0]['payment_intent_id']);
        $this->assertNull($result[0]['amount_received']);
    }

    #[Test]
    public function get_sessions_by_metadata_handles_null_customer(): void
    {
        // Arrange
        $metadataFilters = ['user_id' => 'user_123'];
        $status = 'complete';

        // Mock de sesión sin customer
        $stripeSession = new \stdClass();
        $stripeSession->id = 'cs_test_123';
        $stripeSession->amount_total = 10000;
        $stripeSession->payment_status = 'paid';
        $stripeSession->metadata = (object)['user_id' => 'user_123'];
        $stripeSession->created = 1678901234;
        $stripeSession->customer = null; // Sin customer
        $stripeSession->payment_intent = null;

        $searchResult = new \stdClass();
        $searchResult->data = [$stripeSession];

        // Mock de Session::search
        $searchMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $searchMock->shouldReceive('search')
            ->andReturn($searchResult);

        // Mock del logger
        $loggerMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $loggerMock->shouldReceive('debug');

        // Act
        $result = $this->gatewayQuery->getSessionsByMetadata($metadataFilters, $status);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('cs_test_123', $result[0]['id']);
        $this->assertNull($result[0]['customer']);
    }

    #[Test]
    public function get_sessions_by_metadata_returns_empty_array_on_stripe_api_error(): void
    {
        // Arrange
        $metadataFilters = ['user_id' => 'user_123'];
        $status = 'complete';

        // Mock de Session::search para lanzar excepción
        $searchMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $exception = new \Exception('Stripe API error');

        $searchMock->shouldReceive('search')
            ->andThrow($exception);

        // Mock del logger para error
        $loggerMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $loggerMock->shouldReceive('error')
            ->withArgs(function ($message) {
                return str_contains($message, 'Error obteniendo sesiones de Stripe');
            });

        // Act
        $result = $this->gatewayQuery->getSessionsByMetadata($metadataFilters, $status);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_sessions_by_metadata_with_multiple_metadata_filters(): void
    {
        // Arrange
        $metadataFilters = [
            'user_id' => 'user_123',
            'invoice_id' => 'inv_789',
            'plan' => 'premium',
        ];
        $status = 'open';

        $searchResult = new \stdClass();
        $searchResult->data = [];

        // Mock de Session::search
        $searchMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $searchMock->shouldReceive('search')
            ->with([
                'query' => "metadata['user_id']:'user_123' AND metadata['invoice_id']:'inv_789' AND metadata['plan']:'premium' AND status:'open'",
                'limit' => 100,
                'expand' => 'data.payment_intent'
            ])
            ->andReturn($searchResult);

        // Mock del logger
        $loggerMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $loggerMock->shouldReceive('debug');

        // Act
        $result = $this->gatewayQuery->getSessionsByMetadata($metadataFilters, $status);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }


    #[Test]
    public function get_sessions_by_metadata_with_payment_intent_expansion(): void
    {
        // Arrange
        $metadataFilters = ['user_id' => 'user_123'];
        $status = 'complete';

        // Mock de payment intent expandido
        $paymentIntent = new \stdClass();
        $paymentIntent->id = 'pi_123';
        $paymentIntent->amount_received = 10000;

        // Mock de sesión con payment intent expandido
        $stripeSession = new \stdClass();
        $stripeSession->id = 'cs_test_123';
        $stripeSession->amount_total = 10000;
        $stripeSession->payment_status = 'paid';
        $stripeSession->metadata = (object)['user_id' => 'user_123'];
        $stripeSession->created = 1678901234;
        $stripeSession->customer = 'cus_123';
        $stripeSession->payment_intent = $paymentIntent;

        $searchResult = new \stdClass();
        $searchResult->data = [$stripeSession];

        // Mock de Session::search con expand
        $searchMock = Mockery::mock('overload:Stripe\Checkout\Session');
        $searchMock->shouldReceive('search')
            ->with([
                'query' => "metadata['user_id']:'user_123' AND status:'complete'",
                'limit' => 100,
                'expand' => 'data.payment_intent'
            ])
            ->andReturn($searchResult);

        // Mock del logger
        $loggerMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $loggerMock->shouldReceive('debug');

        // Act
        $result = $this->gatewayQuery->getSessionsByMetadata($metadataFilters, $status);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('pi_123', $result[0]['payment_intent_id']);
        $this->assertEquals(10000, $result[0]['amount_received']);
    }

}
