<?php

namespace Tests\Unit\Domain\Repositories\Stripe;

use Tests\Stubs\Gateways\Stripe\StripeGatewayStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryNoDatabaseTestCase;
use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use Stripe\Checkout\Session;
use PHPUnit\Framework\Attributes\Test;
use InvalidArgumentException;

class StripeGatewayInterfaceTest extends BaseRepositoryNoDatabaseTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = StripeGatewayInterface::class;

    /**
     * Setup the gateway instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos un stub para probar el contrato
        $this->repository = new StripeGatewayStub();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository, 'El gateway de Stripe no está inicializado');
        $this->assertImplementsInterface($this->interfaceClass);
    }

    #[Test]
    public function it_has_all_required_methods(): void
    {
        $this->assertNotNull($this->repository, 'El gateway de Stripe no está inicializado');

        $methods = [
            'createStripeUser',
            'createSetupSession',
            'createCheckoutSession',
            'deletePaymentMethod',
            'expireSessionIfPending',
            'createPayout'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_create_stripe_user(): void
    {
        $user = $this->createMockUser();
        $stripeCustomerId = $this->repository->createStripeUser($user);

        $this->assertIsString($stripeCustomerId);
        $this->assertNotEmpty($stripeCustomerId);
    }

    #[Test]
    public function it_can_create_setup_session(): void
    {
        $user = $this->createMockUser();
        $session = $this->repository->createSetupSession($user->stripe_customer_id);

        $this->assertInstanceOf(Session::class, $session);
        $this->assertNotNull($session->id);
        $this->assertEquals('setup', $session->mode);
    }

    #[Test]
    public function it_can_create_checkout_session(): void
    {
        $user = $this->createMockUser();
        $paymentConcept = $this->createMockPaymentConcept();
        $amount = '100.00';

        $session = $this->repository->createCheckoutSession($user->stripe_customer_id, $paymentConcept, $amount, $user->id);

        $this->assertInstanceOf(Session::class, $session);
        $this->assertNotNull($session->id);
        $this->assertEquals('payment', $session->mode);
        $this->assertNotNull($session->amount_total);
    }

    #[Test]
    public function it_can_delete_payment_method(): void
    {
        $paymentMethodId = 'pm_123456789';
        $result = $this->repository->deletePaymentMethod($paymentMethodId);

        $this->assertIsBool($result);
    }

    #[Test]
    public function it_can_expire_pending_session(): void
    {
        $sessionId = 'cs_123456789';
        $result = $this->repository->expireSessionIfPending($sessionId);

        $this->assertIsBool($result);
    }

    #[Test]
    public function it_can_create_payout(): void
    {
        $payout = $this->repository->createPayout();

        $this->assertIsArray($payout);
        $this->assertArrayHasKey('payout_id', $payout);
        $this->assertArrayHasKey('amount', $payout);
        $this->assertArrayHasKey('currency', $payout);
        $this->assertArrayHasKey('status', $payout);
    }

    #[Test]
    public function create_checkout_session_requires_valid_amount(): void
    {
        $user = $this->createMockUser();
        $paymentConcept = $this->createMockPaymentConcept();

        $testAmounts = ['100.00', '50.50', '0.99', '1000'];

        foreach ($testAmounts as $amount) {
            $session = $this->repository->createCheckoutSession($user->stripe_customer_id, $paymentConcept, $amount, $user->id);
            $this->assertInstanceOf(Session::class, $session);
            $this->assertNotNull($session->id);
        }
    }

    #[Test]
    public function it_handles_user_without_stripe_customer_id(): void
    {
        $user = $this->createMockUser(['stripe_customer_id' => null]);

        // Debería crear un nuevo customer en Stripe
        $stripeCustomerId = $this->repository->createStripeUser($user);

        $this->assertIsString($stripeCustomerId);
        $this->assertNotEmpty($stripeCustomerId);
    }

    #[Test]
    public function it_handles_user_with_existing_stripe_customer_id(): void
    {
        $existingCustomerId = 'cus_123456789';
        $user = $this->createMockUser(['stripe_customer_id' => $existingCustomerId]);

        // Debería retornar el mismo ID o actualizar el customer
        $stripeCustomerId = $this->repository->createStripeUser($user);

        $this->assertIsString($stripeCustomerId);
        $this->assertNotEmpty($stripeCustomerId);
    }

    #[Test]
    public function setup_session_returns_correct_urls(): void
    {
        $user = $this->createMockUser();
        $session = $this->repository->createSetupSession($user->stripe_customer_id);

        $this->assertNotNull($session->url);
        $this->assertStringContainsString('setup-success', $session->url);
        $this->assertNotNull($session->success_url);
        $this->assertNotNull($session->cancel_url);
    }

    #[Test]
    public function checkout_session_includes_payment_concept_data(): void
    {
        $user = $this->createMockUser();
        $paymentConcept = $this->createMockPaymentConcept([
            'concept_name' => 'Matrícula Semestral',
            'description' => 'Pago de matrícula para el semestre 2024-1'
        ]);
        $amount = '500.00';

        $session = $this->repository->createCheckoutSession($user->stripe_customer_id, $paymentConcept, $amount, $user->id);

        $this->assertNotNull($session->metadata);
        $this->assertEquals('Matrícula Semestral', $session->metadata->concept_name ?? '');

    }

    #[Test]
    public function delete_payment_method_handles_invalid_ids(): void
    {
        // ID inválido debería retornar false o lanzar excepción
        $invalidId = 'invalid_payment_method_id';

        try {
            $result = $this->repository->deletePaymentMethod($invalidId);
            $this->assertIsBool($result);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    #[Test]
    public function expire_session_handles_non_pending_sessions(): void
    {
        // Sesión que ya expiró o completó
        $completedSessionId = 'cs_completed_123';
        $result = $this->repository->expireSessionIfPending($completedSessionId);

        $this->assertIsBool($result);
        // Depende de la implementación, podría ser true o false
    }

    #[Test]
    public function create_payout_includes_required_metadata(): void
    {
        $payout = $this->repository->createPayout();

        // Verificar campos requeridos - ajustar según lo que realmente devuelve el stub
        $this->assertIsArray($payout);
        $this->assertArrayHasKey('payout_id', $payout);
        $this->assertArrayHasKey('amount', $payout);
        $this->assertArrayHasKey('currency', $payout);
        $this->assertArrayHasKey('status', $payout);

        // Validar tipos de datos
        $this->assertIsNumeric($payout['amount']);
        $this->assertIsString($payout['currency']);
        $this->assertIsString($payout['status']);
        $this->assertContains($payout['status'], ['pending', 'paid', 'failed', 'canceled']);
    }

    #[Test]
    public function sessions_have_correct_metadata(): void
    {
        $user = $this->createMockUser(['id' => 123]);
        $paymentConcept = $this->createMockPaymentConcept(['id' => 456]);
        $amount = '100.00';

        $session = $this->repository->createCheckoutSession($user->stripe_customer_id, $paymentConcept, $amount, $user->id);

        $this->assertNotNull($session->metadata);
        $this->assertEquals('456', $session->metadata->payment_concept_id ?? '');
    }

    /**
     * Helper para crear mock de User
     */
    private function createMockUser(array $attributes = []): User
    {
        $user = $this->createMock(User::class);

        $defaults = [
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'stripe_customer_id' => 'cus_test123'
        ];

        $attributes = array_merge($defaults, $attributes);

        foreach ($attributes as $key => $value) {
            if (method_exists($user, $key)) {
                $user->method($key)->willReturn($value);
            } else {
                $user->$key = $value;
            }
        }

        return $user;
    }

    /**
     * Helper para crear mock de PaymentConcept
     */
    private function createMockPaymentConcept(array $attributes = []): PaymentConcept
    {
        $concept = $this->createMock(PaymentConcept::class);

        $defaults = [
            'id' => 1,
            'concept_name' => 'Test Concept',
            'amount' => '100.00',
            'description' => 'Test payment concept'
        ];

        $attributes = array_merge($defaults, $attributes);

        foreach ($attributes as $key => $value) {
            if (method_exists($concept, $key)) {
                $concept->method($key)->willReturn($value);
            } else {
                $concept->$key = $value;
            }
        }

        return $concept;
    }

    #[Test]
    public function it_handles_api_errors_gracefully(): void
    {
        $stub = new StripeGatewayStub();
        $stub->shouldThrowApiError(true, 'Error al crear el cliente en Stripe');

        $user = $this->createMockUser();

        $this->expectException(\App\Exceptions\ServerError\StripeGatewayException::class);
        $this->expectExceptionMessage('Error al crear el cliente en Stripe');

        $stub->createStripeUser($user);
    }

    #[Test]
    public function it_handles_rate_limit_errors(): void
    {
        $stub = new StripeGatewayStub();
        $stub->shouldThrowRateLimit(true);

        $user = $this->createMockUser();

        $this->expectException(\App\Exceptions\ServerError\StripeGatewayException::class);
        $this->expectExceptionMessage('Limite de peticiones superado');

        $stub->createSetupSession($user->stripe_customer_id);
    }

    #[Test]
    public function it_validates_payment_method_ids(): void
    {
        $stub = new StripeGatewayStub();

        // ID inválido debería lanzar InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $stub->deletePaymentMethod('invalid_id');
    }

    #[Test]
    public function it_validates_session_ids(): void
    {
        $stub = new StripeGatewayStub();

        // ID inválido no debería lanzar excepción (solo retorna false)
        $result = $stub->expireSessionIfPending('invalid_session_id');
        $this->assertFalse($result);
    }

    #[Test]
    public function it_validates_payout_minimum_amount(): void
    {
        $stub = new StripeGatewayStub();
        $stub->shouldThrowPayoutValidation(true);

        $this->expectException(\App\Exceptions\Validation\PayoutValidationException::class);
        $this->expectExceptionMessage('Fondos insuficientes');

        $stub->createPayout();
    }

    #[Test]
    public function it_uses_mxn_currency_for_checkout(): void
    {
        $user = $this->createMockUser();
        $paymentConcept = $this->createMockPaymentConcept();
        $amount = '500.00';

        $session = $this->repository->createCheckoutSession($user->stripe_customer_id, $paymentConcept, $amount, $user->id);

        $this->assertIsArray($session->line_items);
        $this->assertEquals('mxn', $session->line_items[0]['price_data']['currency'] ?? '');
    }

    #[Test]
    public function it_includes_payment_method_options(): void
    {
        $user = $this->createMockUser();
        $session = $this->repository->createSetupSession($user->stripe_customer_id);

        $this->assertIsArray($session->payment_method_types);
        $this->assertContains('card', $session->payment_method_types);

        $checkoutSession = $this->repository->createCheckoutSession(
            $user->stripe_customer_id,
            $this->createMockPaymentConcept(),
            '100.00',
            $user->id
        );

        $this->assertIsArray($checkoutSession->payment_method_types);
        $this->assertContains('oxxo', $checkoutSession->payment_method_types);
        $this->assertContains('customer_balance', $checkoutSession->payment_method_types);
    }


}
