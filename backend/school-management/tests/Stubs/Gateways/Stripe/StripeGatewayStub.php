<?php

namespace Tests\Stubs\Gateways\Stripe;

use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Exceptions\ServerError\StripeGatewayException;
use App\Exceptions\Validation\PayoutValidationException;
use Stripe\Checkout\Session;
use Stripe\StripeObject;
use InvalidArgumentException;

class StripeGatewayStub implements StripeGatewayInterface
{
    private array $callLog = [];
    private array $stripeCustomers = [];
    private array $sessions = [];
    private array $paymentMethods = [];
    private array $balances = [];
    private array $payouts = [];
    private int $nextCustomerId = 1;
    private int $nextSessionId = 1;
    private int $nextPaymentMethodId = 1;
    private int $nextPayoutId = 1;
    private bool $shouldThrowApiError = false;
    private bool $shouldThrowRateLimit = false;
    private bool $shouldThrowInvalidArgument = false;
    private bool $shouldThrowPayoutValidation = false;
    private ?string $apiErrorMessage = null;
    private array $customerEmails = [];

    public function createStripeUser(User $user): string
    {
        $this->logCall('createStripeUser', [$user]);

        if ($this->shouldThrowInvalidArgument) {
            throw new InvalidArgumentException('User validation failed');
        }

        if ($this->shouldThrowApiError) {
            throw new StripeGatewayException($this->apiErrorMessage ?? 'Error al crear el cliente en Stripe', 500);
        }

        // Si ya tiene customer ID, lo retorna
        if ($user->stripe_customer_id && isset($this->stripeCustomers[$user->stripe_customer_id])) {
            return $user->stripe_customer_id;
        }

        // Buscar por email (como hace tu implementación real)
        foreach ($this->stripeCustomers as $customer) {
            if ($customer['email'] === $user->email) {
                $user->stripe_customer_id = $customer['id'];
                return $customer['id'];
            }
        }

        // Crear nuevo customer
        $customerId = 'cus_' . $this->nextCustomerId++;
        $this->stripeCustomers[$customerId] = [
            'id' => $customerId,
            'email' => $user->email,
            'name' => $user->fullName(),
            'metadata' => ['user_id' => $user->id]
        ];

        $user->stripe_customer_id = $customerId;
        return $customerId;
    }

    public function createSetupSession(string $customerId): Session
    {
        $this->logCall('createSetupSession', [$customerId]);

        if ($this->shouldThrowApiError) {
            throw new StripeGatewayException($this->apiErrorMessage ?? 'Error al crear la sesión setup', 500);
        }

        if ($this->shouldThrowRateLimit) {
            throw new StripeGatewayException('Limite de peticiones superado, espera un momento', 500);
        }

        $sessionId = 'set_' . $this->nextSessionId++;

        $sessionData = [
            'id' => $sessionId,
            'mode' => 'setup',
            'payment_method_types' => ['card'],
            'customer' => $customerId,
            'url' => config('app.frontend_url', 'https://example.com') . "/setup-success?session_id=$sessionId",
            'success_url' => config('app.frontend_url', 'https://example.com') . "/setup-success?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => config('app.frontend_url', 'https://example.com') . "/setup-cancel",
            'status' => 'open'
        ];

        $session = Session::constructFrom($sessionData);

        $this->sessions[$sessionId] = $session;

        return $session;
    }

    public function createCheckoutSession(string $customerId, PaymentConcept $paymentConcept, string $amount, int $userId): Session
    {
        $this->logCall('createCheckoutSession', [$customerId, $paymentConcept, $amount]);

        if ($this->shouldThrowApiError) {
            throw new StripeGatewayException($this->apiErrorMessage ?? 'Error al crear la sesión', 500);
        }

        if ($this->shouldThrowRateLimit) {
            throw new StripeGatewayException('Se alcanzo el limite de intentos, espera un momento', 500);
        }

        $sessionId = 'cs_' . $this->nextSessionId++;

        $amountInCents = (int) (floatval($amount) * 100);

        // Crear el objeto con los datos directamente
        $sessionData = [
            'id' => $sessionId,
            'mode' => 'payment',
            'customer' => $customerId,
            'customer_update' => ['address' => 'auto'],
            'url' => config('app.frontend_url', 'https://example.com') . "/payment-success?session_id=$sessionId",
            'success_url' => config('app.frontend_url', 'https://example.com') . "/payment-success?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => config('app.frontend_url', 'https://example.com') . "/payment-cancel",
            'status' => 'open',
            'payment_status' => 'unpaid',
            'amount_total' => $amountInCents,
            'metadata' => [
                'payment_concept_id' => (string) $paymentConcept->id,
                'concept_name' => $paymentConcept->concept_name,
                'user_id' => $userId
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'mxn',
                    'product_data' => ['name' => $paymentConcept->concept_name],
                    'unit_amount' => $amountInCents,
                ],
                'quantity' => 1,
            ]],
            'payment_method_types' => ['card', 'oxxo', 'customer_balance'],
            'payment_method_options' => [
                'card' => ['setup_future_usage' => 'off_session'],
                'customer_balance' => [
                    'funding_type' => 'bank_transfer',
                    'bank_transfer' => ['type' => 'mx_bank_transfer'],
                ],
            ],
            'saved_payment_method_options' => ['payment_method_save' => 'enabled']
        ];

        $session = Session::constructFrom($sessionData);

        $this->sessions[$sessionId] = $session;

        return $session;
    }

    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        $this->logCall('deletePaymentMethod', [$paymentMethodId]);

        if ($this->shouldThrowInvalidArgument) {
            throw new InvalidArgumentException('ID de método de pago inválido');
        }

        if ($this->shouldThrowApiError) {
            throw new StripeGatewayException('Error eliminando el método de pago', 500);
        }

        // Validar formato del ID (como hace StripeValidator)
        if (!str_starts_with($paymentMethodId, 'pm_')) {
            throw new InvalidArgumentException('ID de método de pago inválido');
        }

        if (!isset($this->paymentMethods[$paymentMethodId])) {
            return false;
        }

        unset($this->paymentMethods[$paymentMethodId]);
        return true;
    }

    public function expireSessionIfPending(string $sessionId): bool
    {
        $this->logCall('expireSessionIfPending', [$sessionId]);

        // Validar formato del ID (como hace StripeValidator)
        if (!str_starts_with($sessionId, 'cs_') && !str_starts_with($sessionId, 'set_')) {
            // En tu implementación real, el validator lanza excepción
            // En el stub, solo retornamos false
            return false;
        }

        if (!isset($this->sessions[$sessionId])) {
            return false;
        }

        $session = $this->sessions[$sessionId];

        // Obtener estados no pagados (como hace tu implementación)
        $nonPaidStatuses = array_map(
            fn($status) => $status->value,
            PaymentStatus::nonPaidStatuses()
        );

        if (in_array($session->payment_status ?? 'unpaid', $nonPaidStatuses, true)) {
            $session->status = 'expired';
            return true;
        }

        return false;
    }

    public function createPayout(): array
    {
        $this->logCall('createPayout', []);

        if ($this->shouldThrowApiError) {
            throw new StripeGatewayException('Error al crear payout: API Error', 500);
        }

        if ($this->shouldThrowPayoutValidation) {
            throw new PayoutValidationException('Fondos insuficientes. Disponible: $50.00 MXN. Mínimo requerido: $100.00 MXN');
        }

        // Simular balance (como tu implementación)
        $totalAvailableMxn = 15000; // $150.00 MXN en centavos
        $minimumPayout = 10000; // $100.00 MXN en centavos

        if ($totalAvailableMxn < $minimumPayout) {
            $availableFormatted = number_format($totalAvailableMxn / 100, 2);
            throw new PayoutValidationException(
                "Fondos insuficientes. Disponible: $" .
                $availableFormatted . " MXN. " .
                "Mínimo requerido: $100.00 MXN"
            );
        }

        $payoutId = 'po_' . $this->nextPayoutId++;

        $this->payouts[$payoutId] = [
            'id' => $payoutId,
            'amount' => $totalAvailableMxn,
            'currency' => 'mxn',
            'status' => 'pending',
            'arrival_date' => time() + 86400, // +1 día
            'description' => 'Payout manual de la escuela'
        ];

        return [
            'success' => true,
            'payout_id' => $payoutId,
            'amount' => $totalAvailableMxn / 100,
            'currency' => 'mxn',
            'arrival_date' => date('Y-m-d', time() + 86400),
            'status' => 'pending',
            'available_before_payout' => $totalAvailableMxn / 100,
        ];
    }

    // ========== MÉTODOS DE CONFIGURACIÓN PARA TESTING ==========

    public function shouldThrowApiError(bool $throw = true, ?string $message = null): self
    {
        $this->shouldThrowApiError = $throw;
        $this->apiErrorMessage = $message;
        return $this;
    }

    public function shouldThrowRateLimit(bool $throw = true): self
    {
        $this->shouldThrowRateLimit = $throw;
        return $this;
    }

    public function shouldThrowInvalidArgument(bool $throw = true): self
    {
        $this->shouldThrowInvalidArgument = $throw;
        return $this;
    }

    public function shouldThrowPayoutValidation(bool $throw = true): self
    {
        $this->shouldThrowPayoutValidation = $throw;
        return $this;
    }

    public function setBalance(float $amountMxn): self
    {
        $this->balances['mxn'] = (int) ($amountMxn * 100);
        return $this;
    }

    public function addCustomer(string $customerId, array $data): self
    {
        $this->stripeCustomers[$customerId] = $data;
        return $this;
    }

    public function addSession(string $sessionId, array $data, string $paymentStatus = 'unpaid'): self
    {
        $session = new StripeObject();
        $session->id = $sessionId;
        $session->payment_status = $paymentStatus;
        $session->status = 'open';

        foreach ($data as $key => $value) {
            $session->$key = $value;
        }

        $this->sessions[$sessionId] = $session;
        return $this;
    }

    public function addPaymentMethod(string $paymentMethodId, array $data = []): self
    {
        $this->paymentMethods[$paymentMethodId] = $data;
        return $this;
    }

    // ========== MÉTODOS DE CONSULTA PARA TESTING ==========

    public function getCallLog(): array
    {
        return $this->callLog;
    }

    public function getMethodCalls(string $methodName): array
    {
        return array_filter($this->callLog, fn($call) => $call['method'] === $methodName);
    }

    public function getCallCount(string $methodName): int
    {
        return count($this->getMethodCalls($methodName));
    }

    public function getLastCall(string $methodName): ?array
    {
        $calls = $this->getMethodCalls($methodName);
        return !empty($calls) ? end($calls) : null;
    }

    public function getCustomer(string $customerId): ?array
    {
        return $this->stripeCustomers[$customerId] ?? null;
    }

    public function getSession(string $sessionId): ?StripeObject
    {
        return $this->sessions[$sessionId] ?? null;
    }

    public function getPaymentMethod(string $paymentMethodId): ?array
    {
        return $this->paymentMethods[$paymentMethodId] ?? null;
    }

    public function getPayout(string $payoutId): ?array
    {
        return $this->payouts[$payoutId] ?? null;
    }

    public function clearData(): void
    {
        $this->stripeCustomers = [];
        $this->sessions = [];
        $this->paymentMethods = [];
        $this->payouts = [];
        $this->balances = [];
        $this->callLog = [];
        $this->nextCustomerId = 1;
        $this->nextSessionId = 1;
        $this->nextPaymentMethodId = 1;
        $this->nextPayoutId = 1;
        $this->shouldThrowApiError = false;
        $this->shouldThrowRateLimit = false;
        $this->shouldThrowInvalidArgument = false;
        $this->shouldThrowPayoutValidation = false;
        $this->apiErrorMessage = null;
    }

    private function logCall(string $method, array $args): void
    {
        // Serializar objetos complejos
        $serializedArgs = array_map(function($arg) {
            if (is_object($arg)) {
                $class = get_class($arg);
                if ($arg instanceof User) {
                    return "User#{$arg->id} ({$arg->email})";
                }
                if ($arg instanceof PaymentConcept) {
                    return "PaymentConcept#{$arg->id} ({$arg->concept_name})";
                }
                return "{$class} object";
            }
            if (is_array($arg)) {
                return 'array[' . count($arg) . ']';
            }
            return $arg;
        }, $args);

        $this->callLog[] = [
            'method' => $method,
            'args' => $serializedArgs,
            'timestamp' => microtime(true)
        ];
    }

}
