<?php

namespace Tests\Stubs\Repositories\Query;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Entities\PaymentMethod;

class PaymentMethodQueryRepStub implements PaymentMethodQueryRepInterface
{
    private ?PaymentMethod $nextFindByIdResult = null;
    private ?PaymentMethod $nextFindByStripeIdResult = null;
    private array $nextFindByStripeIdsResult = [];
    private array $nextGetByUserIdResult = [];
    private bool $existsPaymentMethod = false;

    public function findById(int $id): ?PaymentMethod
    {
        return $this->nextFindByIdResult;
    }

    public function findByStripeId(string $stripeId): ?PaymentMethod
    {
        return $this->nextFindByStripeIdResult;
    }

    public function findByStripeIds(array $stripeIds): array
    {
        return $this->nextFindByStripeIdsResult;
    }

    public function getByUserId(int $userId): array
    {
        return $this->nextGetByUserIdResult;
    }

    public function existsPaymentMethodByStripeId(string $stripeId): bool
    {
        return $this->existsPaymentMethod;
    }

    // Métodos de configuración
    public function setNextFindByIdResult(?PaymentMethod $method): self
    {
        $this->nextFindByIdResult = $method;
        return $this;
    }

    public function setNextFindByStripeIdResult(?PaymentMethod $method): self
    {
        $this->nextFindByStripeIdResult = $method;
        return $this;
    }

    public function setNextFindByStripeIdsResult(array $methods): self
    {
        $this->nextFindByStripeIdsResult = $methods;
        return $this;
    }

    public function setNextGetByUserIdResult(array $methods): self
    {
        $this->nextGetByUserIdResult = $methods;
        return $this;
    }

    public function setExistPaymentMethod(bool $existsPaymentMethod): self
    {
        $this->existsPaymentMethod = $existsPaymentMethod;
        return $this;
    }
}
