<?php

namespace Tests\Stubs\Repositories\Command;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Domain\Entities\PaymentMethod;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentMethodRepStub implements PaymentMethodRepInterface
{
    private bool $throwDatabaseError = false;
    private array $paymentMethods = [];
    private int $nextId = 1;

    public function __construct()
    {
        $this->initializeTestData();
    }

    private function initializeTestData(): void
    {
        // Métodos de pago de prueba iniciales
        $this->paymentMethods = [
            1 => new PaymentMethod(1, 'pm_test_001', 'Visa', '4242', 12, 2027, 1),
            2 => new PaymentMethod(2, 'pm_test_002', 'Mastercard', '5678', 6, 2025, 2),
            3 => new PaymentMethod(3, 'pm_test_003', 'Amex', '1234', 1, 2023, 3), // Expirado
        ];
        $this->nextId = 4;
    }

    public function create(PaymentMethod $paymentMethod): PaymentMethod
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $id = $paymentMethod->id ?? $this->nextId++;

        $newPaymentMethod = new PaymentMethod(
            $paymentMethod->user_id,
            $paymentMethod->stripe_payment_method_id,
            $paymentMethod->brand,
            $paymentMethod->last4,
            $paymentMethod->exp_month,
            $paymentMethod->exp_year,
            $id
        );

        $this->paymentMethods[$id] = $newPaymentMethod;

        return $newPaymentMethod;
    }

    public function delete(int $paymentMethodId): void
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        if (!isset($this->paymentMethods[$paymentMethodId])) {
            throw new ModelNotFoundException('Payment method not found');
        }

        unset($this->paymentMethods[$paymentMethodId]);
    }

    // Métodos de configuración para pruebas

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function addPaymentMethod(PaymentMethod $paymentMethod): self
    {
        $id = $paymentMethod->id ?? $this->nextId++;

        if ($paymentMethod->id === null) {
            $paymentMethodWithId = new PaymentMethod(
                $paymentMethod->user_id,
                $paymentMethod->stripe_payment_method_id,
                $paymentMethod->brand,
                $paymentMethod->last4,
                $paymentMethod->exp_month,
                $paymentMethod->exp_year,
                $id
            );
            $this->paymentMethods[$id] = $paymentMethodWithId;
        } else {
            $this->paymentMethods[$id] = $paymentMethod;
            if ($id >= $this->nextId) {
                $this->nextId = $id + 1;
            }
        }

        return $this;
    }

    public function deleteByStripeId(string $stripeId): bool
    {
        return true;
    }

    public function updateByStripeId(string $stripeId, array $fields): int
    {
        return 1;
    }

    public function getPaymentMethod(int $id): ?PaymentMethod
    {
        return $this->paymentMethods[$id] ?? null;
    }

    public function getPaymentMethodsCount(): int
    {
        return count($this->paymentMethods);
    }

    public function clearPaymentMethods(): self
    {
        $this->paymentMethods = [];
        $this->nextId = 1;
        return $this;
    }

    public function getAllPaymentMethods(): array
    {
        return $this->paymentMethods;
    }

    public function getUserPaymentMethods(int $userId): array
    {
        return array_filter($this->paymentMethods, function($method) use ($userId) {
            return $method->user_id === $userId;
        });
    }
}
