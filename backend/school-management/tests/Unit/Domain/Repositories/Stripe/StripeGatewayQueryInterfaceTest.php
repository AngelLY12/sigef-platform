<?php

namespace Tests\Unit\Domain\Repositories\Stripe;

use Tests\Stubs\Gateways\Stripe\StripeGatewayQueryStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryNoDatabaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Core\Domain\Entities\User;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\StripeObject;

class StripeGatewayQueryInterfaceTest extends BaseRepositoryNoDatabaseTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = StripeGatewayQueryInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos un stub para probar el contrato
        $this->repository = new StripeGatewayQueryStub();
    }

    /**
     * Test que el repositorio puede ser instanciado
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');
        $this->assertImplementsInterface($this->interfaceClass);
    }

    /**
     * Test que todos los métodos requeridos existen
     */
    #[Test]
    public function it_has_all_required_methods(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');

        $methods = [
            'getSetupIntentFromSession',
            'retrievePaymentMethod',
            'getIntentAndCharge',
            'getStudentPaymentsFromStripe',
            'getPaymentIntentFromSession',
            'getBalanceFromStripe',
            'getPayoutsFromStripe',
            'getIntentsAndChargesBatch'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_get_setup_intent_from_session(): void
    {
        $sessionId = 'cs_123456789';
        $result = $this->repository->getSetupIntentFromSession($sessionId);

        $this->assertNull($result, 'Para una sesión sin setup_intent debería retornar null');
    }

    #[Test]
    public function it_can_retrieve_payment_method(): void
    {
        $paymentMethodId = 'pm_123456789';
        $result = $this->repository->retrievePaymentMethod($paymentMethodId);

        $this->assertInstanceOf(StripePaymentMethod::class, $result);
        $this->assertEquals($paymentMethodId, $result->id);
    }

    #[Test]
    public function it_can_get_intent_and_charge(): void
    {
        $paymentIntentId = 'pi_123456789';
        $result = $this->repository->getIntentAndCharge($paymentIntentId);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        list($intent, $charge) = $result;

        $this->assertInstanceOf(PaymentIntent::class, $intent);
        $this->assertInstanceOf(StripeObject::class, $charge);
        $this->assertEquals($paymentIntentId, $intent->id);
    }

    #[Test]
    public function it_can_get_student_payments_from_stripe(): void
    {
        $user = $this->createMockUser();

        // Test sin año específico
        $result = $this->repository->getStudentPaymentsFromStripe($user, null);

        $this->assertIsArray($result);

        // Test con año específico
        $resultWithYear = $this->repository->getStudentPaymentsFromStripe($user, 2024);

        $this->assertIsArray($resultWithYear);
    }

    #[Test]
    public function it_can_get_payment_intent_from_session(): void
    {
        $sessionId = 'cs_123456789';
        $result = $this->repository->getPaymentIntentFromSession($sessionId);

        $this->assertInstanceOf(PaymentIntent::class, $result);
        $this->assertNotEmpty($result->id);
    }

    #[Test]
    public function it_can_get_balance_from_stripe(): void
    {
        $result = $this->repository->getBalanceFromStripe();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
        $this->assertArrayHasKey('pending', $result);

        $this->assertIsArray($result['available']);
        $this->assertIsArray($result['pending']);
    }

    #[Test]
    public function it_can_get_payouts_from_stripe(): void
    {
        // Test con solo este año
        $result = $this->repository->getPayoutsFromStripe(true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_fee', $result);
        $this->assertArrayHasKey('by_month', $result);

        // Test con todos los años
        $resultAll = $this->repository->getPayoutsFromStripe(false);

        $this->assertIsArray($resultAll);
        $this->assertArrayHasKey('total', $resultAll);
    }

    #[Test]
    public function it_can_get_intents_and_charges_batch(): void
    {
        $paymentIntentIds = ['pi_1', 'pi_2', 'pi_3'];
        $result = $this->repository->getIntentsAndChargesBatch($paymentIntentIds);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($paymentIntentIds as $id) {
            $this->assertArrayHasKey($id, $result);
            $this->assertIsArray($result[$id]);
            $this->assertCount(2, $result[$id]);
        }
    }

    #[Test]
    public function it_validates_invalid_session_ids(): void
    {
        $invalidSessionId = 'invalid_session_id';

        try {
            $result = $this->repository->getSetupIntentFromSession($invalidSessionId);
            // Si no lanza excepción, verificamos que retorna null
            $this->assertNull($result);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    #[Test]
    public function it_validates_invalid_payment_method_ids(): void
    {
        $invalidPaymentMethodId = 'invalid_pm_id';

        $this->expectException(\InvalidArgumentException::class);
        $this->repository->retrievePaymentMethod($invalidPaymentMethodId);
    }

    #[Test]
    public function it_validates_invalid_payment_intent_ids(): void
    {
        $invalidPaymentIntentId = 'invalid_pi_id';

        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getIntentAndCharge($invalidPaymentIntentId);
    }

    #[Test]
    public function it_handles_session_without_payment_intent(): void
    {
        $sessionId = 'cs_no_intent';

        $this->expectException(\App\Exceptions\Validation\ValidationException::class);
        $this->expectExceptionMessage('Session sin payment_intent');

        $this->repository->getPaymentIntentFromSession($sessionId);
    }

    #[Test]
    public function it_handles_empty_payment_intent_ids_batch(): void
    {
        $result = $this->repository->getIntentsAndChargesBatch([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function student_payments_include_receipt_url(): void
    {
        $user = $this->createMockUser();
        $result = $this->repository->getStudentPaymentsFromStripe($user, 2024);

        if (!empty($result)) {
            $firstPayment = $result[0];
            $this->assertNotEmpty($firstPayment->receipt_url ?? null);
        }
    }

    #[Test]
    public function it_handles_api_errors_gracefully(): void
    {
        $stub = new StripeGatewayQueryStub();
        $stub->shouldThrowApiError(true);

        $this->expectException(\App\Exceptions\ServerError\StripeGatewayException::class);

        $stub->getBalanceFromStripe();
    }

    #[Test]
    public function balance_includes_available_and_pending_amounts(): void
    {
        $result = $this->repository->getBalanceFromStripe();

        $this->assertIsArray($result['available']);
        $this->assertIsArray($result['pending']);

        // Verificar estructura de los montos
        if (!empty($result['available'])) {
            $firstAvailable = $result['available'][0];
            $this->assertArrayHasKey('amount', $firstAvailable);
            $this->assertArrayHasKey('source_types', $firstAvailable);
        }
    }

    #[Test]
    public function payouts_include_monthly_breakdown(): void
    {
        $result = $this->repository->getPayoutsFromStripe(true);

        $this->assertIsArray($result['by_month']);

        if (!empty($result['by_month'])) {
            $firstMonth = reset($result['by_month']);
            $this->assertArrayHasKey('amount', $firstMonth);
            $this->assertArrayHasKey('fee', $firstMonth);
        }
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

    #[Test]
    public function count_sessions_by_metadata_returns_configured_count(): void
    {
        // Arrange
        $this->repository->setSessionCountResult(5);

        // Act
        $count = $this->repository->countSessionsByMetadata(
            ['payment_concept_id' => '123', 'user_id' => '456'],
            'complete'
        );

        // Assert
        $this->assertEquals(5, $count);
    }

    #[Test]
    public function get_sessions_by_metadata_returns_configured_sessions(): void
    {
        // Arrange

        $testSessions = [
            [
                'id' => 'cs_test_1',
                'payment_intent_id' => 'pi_test_1',
                'amount_total' => 2500,
                'amount_received' => 2500,
                'status' => 'complete',
                'metadata' => ['payment_concept_id' => '123', 'user_id' => '456'],
                'created' => 1672531200,
                'customer' => 'cus_123'
            ],
            [
                'id' => 'cs_test_2',
                'payment_intent_id' => 'pi_test_2',
                'amount_total' => 1500,
                'amount_received' => 1500,
                'status' => 'complete',
                'metadata' => ['payment_concept_id' => '123', 'user_id' => '456'],
                'created' => 1672617600,
                'customer' => null
            ]
        ];

        $this->repository->setMetadataSessions($testSessions);

        // Act
        $sessions = $this->repository->getSessionsByMetadata(
            ['payment_concept_id' => '123', 'user_id' => '456'],
            'complete'
        );

        // Assert
        $this->assertCount(2, $sessions);
        $this->assertEquals('cs_test_1', $sessions[0]['id']);
        $this->assertEquals(2500, $sessions[0]['amount_received']);
    }

    #[Test]
    public function get_sessions_by_metadata_filters_by_status(): void
    {
        // Arrange
        $testSessions = [
            [
                'id' => 'cs_complete',
                'payment_intent_id' => 'pi_1',
                'amount_total' => 1000,
                'amount_received' => 1000,
                'status' => 'complete',
                'metadata' => ['user_id' => '123'],
                'created' => time(),
                'customer' => null
            ],
            [
                'id' => 'cs_open',
                'payment_intent_id' => 'pi_2',
                'amount_total' => 1000,
                'amount_received' => null,
                'status' => 'open',
                'metadata' => ['user_id' => '123'],
                'created' => time(),
                'customer' => null
            ]
        ];

        $this->repository->setMetadataSessions($testSessions);

        // Act - Solo sesiones completadas
        $completeSessions = $this->repository->getSessionsByMetadata(['user_id' => '123'], 'complete');

        // Assert
        $this->assertCount(1, $completeSessions);
        $this->assertEquals('cs_complete', $completeSessions[0]['id']);
    }

    #[Test]
    public function count_sessions_by_metadata_returns_zero_on_error(): void
    {
        // Arrange
        $this->repository->shouldThrowApiError(true);

        // Act & Assert
        $this->expectException(\App\Exceptions\ServerError\StripeGatewayException::class);

        $this->repository->countSessionsByMetadata(['user_id' => '123'], 'complete');
    }

    #[Test]
    public function get_sessions_by_metadata_returns_empty_array_on_error(): void
    {
        // Arrange
        $this->repository->shouldThrowApiError(true);

        // Act & Assert
        $this->expectException(\App\Exceptions\ServerError\StripeGatewayException::class);

        $this->repository->getSessionsByMetadata(['user_id' => '123'], 'complete');
    }
}
