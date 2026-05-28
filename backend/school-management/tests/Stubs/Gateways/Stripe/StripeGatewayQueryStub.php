<?php

namespace Tests\Stubs\Gateways\Stripe;

use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use Stripe\Charge;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\StripeObject;

class StripeGatewayQueryStub implements StripeGatewayQueryInterface
{
    private bool $shouldThrowApiError = false;
    private bool $shouldThrowValidationError = false;
    private array $sessions = [];
    private array $paymentMethods = [];
    private array $paymentIntents = [];
    private array $charges = [];
    private array $studentPayments = [];
    private array $balances = [];
    private array $payouts = [];
    private int $nextSessionId = 1;
    private int $nextPaymentMethodId = 1;
    private int $nextPaymentIntentId = 1;
    private int $nextChargeId = 1;
    private array $metadataSessions = [];
    private int $sessionCountResult = 0;

    public function __construct()
    {
        $this->initializeTestData();
    }

    private function initializeTestData(): void
    {
        $this->balances = [
            'available' => [
                [
                    'amount' => '1500.00',
                    'source_types' => ['card' => 150000]
                ]
            ],
            'pending' => [
                [
                    'amount' => '500.00',
                    'source_types' => ['card' => 50000]
                ]
            ]
        ];

        $this->payouts = [
            'total' => '1000.00',
            'total_fee' => '50.00',
            'by_month' => [
                '2024-01' => ['amount' => '500.00', 'fee' => '25.00'],
                '2024-02' => ['amount' => '500.00', 'fee' => '25.00']
            ]
        ];

        $this->studentPayments = [
            $this->createMockSession([
                'id' => 'cs_student_1',
                'payment_intent' => 'pi_student_1',
                'payment_status' => 'paid',
                'amount_total' => 10000,
                'receipt_url' => 'https://receipt.stripe.com/test'
            ])
        ];

        $this->paymentIntents['pi_123456789'] = PaymentIntent::constructFrom([
            'id' => 'pi_123456789',
            'amount' => 10000,
            'currency' => 'mxn',
            'status' => 'succeeded',
            'charges' => ['data' => [
                Charge::constructFrom([
                    'id' => 'ch_123456789',
                    'amount' => 10000,
                    'currency' => 'mxn',
                    'receipt_url' => 'https://receipt.stripe.com/test'
                ])
            ]]
        ]);

        $this->paymentMethods['pm_123456789'] = PaymentMethod::constructFrom([
            'id' => 'pm_123456789',
            'type' => 'card',
            'card' => ['last4' => '4242']
        ]);

        $this->metadataSessions = [
            [
                'id' => 'cs_metadata_1',
                'payment_intent_id' => 'pi_metadata_1',
                'amount_total' => 10000,
                'amount_received' => 10000,
                'status' => 'paid',
                'metadata' => ['payment_concept_id' => '1', 'user_id' => '100'],
                'created' => 1672531200,
                'customer' => 'cus_metadata_1'
            ],
            [
                'id' => 'cs_metadata_2',
                'payment_intent_id' => 'pi_metadata_2',
                'amount_total' => 5000,
                'amount_received' => 5000,
                'status' => 'paid',
                'metadata' => ['payment_concept_id' => '1', 'user_id' => '100'],
                'created' => 1672617600,
                'customer' => null
            ]
        ];

        $this->sessionCountResult = 2;
    }

    public function getSetupIntentFromSession(string $sessionId)
    {
        $this->validateSessionId($sessionId);

        if ($this->shouldThrowApiError) {
            throw new \App\Exceptions\ServerError\StripeGatewayException("Error trayendo el intent de la sesión", 500);
        }

        // Simular sesión sin setup_intent
        return null;
    }

    public function retrievePaymentMethod(string $paymentMethodId)
    {
        $this->validatePaymentMethodId($paymentMethodId);

        if ($this->shouldThrowApiError) {
            throw new \App\Exceptions\ServerError\StripeGatewayException("Error obteniendo el método de pago", 500);
        }

        if (!isset($this->paymentMethods[$paymentMethodId])) {
            // Crear un nuevo método de pago para el stub
            $this->paymentMethods[$paymentMethodId] = PaymentMethod::constructFrom([
                'id' => $paymentMethodId,
                'type' => 'card',
                'card' => ['last4' => '4242']
            ]);
        }

        return $this->paymentMethods[$paymentMethodId];
    }

    public function getIntentAndCharge(string $paymentIntentId): array
    {
        $this->validatePaymentIntentId($paymentIntentId);

        if ($this->shouldThrowApiError) {
            throw new \App\Exceptions\ServerError\StripeGatewayException("Error obteniendo los datos", 500);
        }

        if (!isset($this->paymentIntents[$paymentIntentId])) {
            // Crear datos de prueba
            $intent = PaymentIntent::constructFrom([
                'id' => $paymentIntentId,
                'amount' => 10000,
                'currency' => 'mxn',
                'status' => 'succeeded',
                'charges' => ['data' => []]
            ]);

            $charge = Charge::constructFrom([
                'id' => 'ch_' . substr($paymentIntentId, 3),
                'amount' => 10000,
                'currency' => 'mxn'
            ]);

            $this->paymentIntents[$paymentIntentId] = $intent;
            $this->charges['ch_' . substr($paymentIntentId, 3)] = $charge;
        }

        $chargeId = 'ch_' . substr($paymentIntentId, 3);
        $charge = $this->charges[$chargeId] ?? Charge::constructFrom([
            'id' => $chargeId,
            'amount' => 10000,
            'currency' => 'mxn'
        ]);

        return [$this->paymentIntents[$paymentIntentId], $charge];
    }

    public function getStudentPaymentsFromStripe(User $user, ?int $year): array
    {
        if ($this->shouldThrowApiError) {
            throw new \App\Exceptions\ServerError\StripeGatewayException("Error obteniendo los pagos del estudiante", 500);
        }

        // Filtrar por año si se especifica
        $payments = $this->studentPayments;

        if ($year) {
            // Simular filtrado por año
            $payments = array_filter($payments, function($payment) use ($year) {
                return true; // Todos pasan en el stub
            });
        }

        return $payments;
    }

    public function getPaymentIntentFromSession(string $sessionId): PaymentIntent
    {
        $this->validateSessionId($sessionId);

        if ($this->shouldThrowApiError) {
            throw new \App\Exceptions\ServerError\StripeGatewayException("Error obteniendo los datos", 500);
        }

        if ($sessionId === 'cs_no_intent') {
            throw new \App\Exceptions\Validation\ValidationException("Session sin payment_intent: {$sessionId}");
        }

        $paymentIntentId = 'pi_' . substr($sessionId, 3);

        if (!isset($this->paymentIntents[$paymentIntentId])) {
            $this->paymentIntents[$paymentIntentId] = PaymentIntent::constructFrom([
                'id' => $paymentIntentId,
                'amount' => 10000,
                'currency' => 'mxn',
                'status' => 'succeeded'
            ]);
        }

        return $this->paymentIntents[$paymentIntentId];
    }

    public function getBalanceFromStripe(): array
    {
        if ($this->shouldThrowApiError) {
            throw new \App\Exceptions\ServerError\StripeGatewayException("Error obteniendo balance", 500);
        }

        return $this->balances;
    }

    public function getPayoutsFromStripe(bool $onlyThisYear = false): array
    {
        if ($this->shouldThrowApiError) {
            throw new \App\Exceptions\ServerError\StripeGatewayException("Error obteniendo payouts", 500);
        }

        if ($onlyThisYear) {
            $currentYear = date('Y');
            $filteredPayouts = $this->payouts;
            // En el stub, retornamos los mismos datos independientemente del año
            return $filteredPayouts;
        }

        return $this->payouts;
    }

    public function getIntentsAndChargesBatch(array $paymentIntentIds): array
    {
        if (empty($paymentIntentIds)) {
            return [];
        }

        $results = [];
        foreach ($paymentIntentIds as $id) {
            try {
                $results[$id] = $this->getIntentAndCharge($id);
            } catch (\Exception $e) {
                // Continuar con los siguientes en el batch
                continue;
            }
        }

        return $results;
    }


    public function countSessionsByMetadata(array $metadata, string $status): int
    {
        if ($this->shouldThrowApiError) {
            throw new \App\Exceptions\ServerError\StripeGatewayException("Error contando sesiones por metadata", 500);
        }

        try {
            $filteredCount = $this->sessionCountResult;

            // Si se quiere hacer matching de metadata en el stub
            if (!empty($metadata)) {
                foreach ($this->metadataSessions as $session) {
                    $matches = true;
                    foreach ($metadata as $key => $value) {
                        if (!isset($session['metadata'][$key]) ||
                            (string)$session['metadata'][$key] !== (string)$value) {
                            $matches = false;
                            break;
                        }
                    }
                    // También verificar status si se necesita
                    if ($matches && isset($session['status']) && $session['status'] === $status) {
                        $filteredCount++;
                    }
                }
            }

            return $filteredCount;

        } catch (\Exception $e) {
            logger()->error("Error contando sesiones por estado: " . $e->getMessage());
            return 0;
        }
    }

    public function getSessionsByMetadata(array $metadataFilters, string $status, int $limit = 100): array
    {
        if ($this->shouldThrowApiError) {
            throw new \App\Exceptions\ServerError\StripeGatewayException("Error obteniendo sesiones por metadata", 500);
        }

        try {
            $sessions = [];

            foreach ($this->metadataSessions as $session) {
                $matches = true;

                foreach ($metadataFilters as $key => $value) {
                    if (!isset($session['metadata'][$key]) ||
                        (string)$session['metadata'][$key] !== (string)$value) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches && isset($session['status']) && $session['status'] !== $status) {
                    $matches = false;
                }

                if ($matches) {
                    $sessions[] = $session;
                }

                if (count($sessions) >= $limit) {
                    break;
                }
            }

            logger()->debug("Encontradas " . count($sessions) . " sesiones con filtros", [
                'filters' => $metadataFilters,
                'session_ids' => array_column($sessions, 'id')
            ]);

            return $sessions;
        } catch (\Exception $e) {
            logger()->error("Error obteniendo sesiones de Stripe: " . $e->getMessage());
            return [];
        }
    }

    public function setMetadataSessions(array $sessions): self
    {
        $this->metadataSessions = $sessions;
        return $this;
    }

    public function addMetadataSession(array $session): self
    {
        $this->metadataSessions[] = $session;
        return $this;
    }

    public function setSessionCountResult(int $count): self
    {
        $this->sessionCountResult = $count;
        return $this;
    }

    public function createPartialPaymentSession(
        string $userId,
        string $paymentConceptId,
        int $amountTotal,
        int $amountReceived,
        string $status = 'paid'
    ): array {
        $sessionId = 'cs_partial_' . uniqid();

        return [
            'id' => $sessionId,
            'payment_intent_id' => 'pi_' . substr($sessionId, 3),
            'amount_total' => $amountTotal,
            'amount_received' => $amountReceived,
            'status' => $status,
            'metadata' => [
                'user_id' => (string)$userId,
                'payment_concept_id' => (string)$paymentConceptId,
            ],
            'created' => time(),
            'customer' => null,
        ];
    }

    private function validateSessionId(string $sessionId): void
    {
        if (!str_starts_with($sessionId, 'cs_') && !str_starts_with($sessionId, 'set_')) {
            throw new \InvalidArgumentException("ID de la sesión inválido");
        }
    }

    private function validatePaymentMethodId(string $paymentMethodId): void
    {
        if (!str_starts_with($paymentMethodId, 'pm_')) {
            throw new \InvalidArgumentException("método de pago inválido");
        }
    }

    private function validatePaymentIntentId(string $paymentIntentId): void
    {
        if (!str_starts_with($paymentIntentId, 'pi_')) {
            throw new \InvalidArgumentException("payment intent inválido");
        }
    }

    private function createMockSession(array $data): StripeObject
    {
        return StripeObject::constructFrom($data);
    }

    public function shouldThrowApiError(bool $throw = true): self
    {
        $this->shouldThrowApiError = $throw;
        return $this;
    }

    public function shouldThrowValidationError(bool $throw = true): self
    {
        $this->shouldThrowValidationError = $throw;
        return $this;
    }
}
