<?php

namespace Tests\Unit\Domain\Repositories\Query;

use Tests\Stubs\Repositories\Query\PaymentMethodQueryRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Entities\PaymentMethod;
use DateTime;
use PHPUnit\Framework\Attributes\Test;

class PaymentMethodQueryRepInterfaceTest extends BaseRepositoryTestCase
{
    protected string $interfaceClass = PaymentMethodQueryRepInterface::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PaymentMethodQueryRepStub();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository);
        $this->assertImplementsInterface($this->interfaceClass);
    }

    #[Test]
    public function it_has_all_required_methods(): void
    {
        $methods = ['findById', 'findByStripeId', 'findByStripeIds', 'getByUserId'];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function findById_returns_payment_method_when_found(): void
    {
        $paymentMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_123',
            brand: 'Visa',
            last4: '4242',
            exp_month: 12,
            exp_year: 2027,
            id: 1
        );

        $this->repository->setNextFindByIdResult($paymentMethod);

        $result = $this->repository->findById(1);

        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('pm_123', $result->stripe_payment_method_id);
        $this->assertEquals('Visa', $result->brand);
        $this->assertEquals('12/27', $result->expirationDate());
        $this->assertFalse($result->isExpired());
    }

    #[Test]
    public function findById_returns_null_when_not_found(): void
    {
        $this->repository->setNextFindByIdResult(null);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    #[Test]
    public function findByStripeId_returns_payment_method_when_found(): void
    {
        $paymentMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_abc123',
            brand: 'Mastercard',
            last4: '1234',
            exp_month: 06,
            exp_year: 2025,
            id: 2
        );

        $this->repository->setNextFindByStripeIdResult($paymentMethod);

        $result = $this->repository->findByStripeId('pm_abc123');

        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertEquals('pm_abc123', $result->stripe_payment_method_id);
        $this->assertEquals('Mastercard', $result->brand);
        $this->assertEquals('**** **** **** 1234', $result->getMaskedCard());
    }

    #[Test]
    public function findByStripeId_returns_null_when_not_found(): void
    {
        $this->repository->setNextFindByStripeIdResult(null);

        $result = $this->repository->findByStripeId('pm_notfound');

        $this->assertNull($result);
    }

    #[Test]
    public function findByStripeIds_returns_array_of_payment_methods(): void
    {
        $methods = [
            new PaymentMethod(
                user_id: 1,
                stripe_payment_method_id: 'pm_1',
                brand: 'Visa',
                last4: '1111',
                id: 1
            ),
            new PaymentMethod(
                user_id: 1,
                stripe_payment_method_id: 'pm_2',
                brand: 'Mastercard',
                last4: '2222',
                id: 2
            ),
        ];

        $this->repository->setNextFindByStripeIdsResult($methods);

        $result = $this->repository->findByStripeIds(['pm_1', 'pm_2']);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(PaymentMethod::class, $result);
        $this->assertEquals('pm_1', $result[0]->stripe_payment_method_id);
        $this->assertEquals('pm_2', $result[1]->stripe_payment_method_id);
    }

    #[Test]
    public function findByStripeIds_returns_empty_array_when_none_found(): void
    {
        $this->repository->setNextFindByStripeIdsResult([]);

        $result = $this->repository->findByStripeIds(['pm_1', 'pm_2']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function getByUserId_returns_array_of_user_payment_methods(): void
    {
        $methods = [
            new PaymentMethod(
                user_id: 1,
                stripe_payment_method_id: 'pm_1',
                brand: 'Visa',
                last4: '1111',
                exp_month: 01,
                exp_year: 2026,
                id: 1
            ),
            new PaymentMethod(
                user_id: 1,
                stripe_payment_method_id: 'pm_2',
                brand: 'Amex',
                last4: '3333',
                exp_month: 12,
                exp_year: 2024,
                id: 2
            ),
        ];

        $this->repository->setNextGetByUserIdResult($methods);

        $result = $this->repository->getByUserId(1);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(PaymentMethod::class, $result);

        // Todos deben pertenecer al mismo usuario
        foreach ($result as $method) {
            $this->assertEquals(1, $method->user_id);
        }
    }

    #[Test]
    public function getByUserId_returns_empty_array_when_user_has_no_methods(): void
    {
        $this->repository->setNextGetByUserIdResult([]);

        $result = $this->repository->getByUserId(999);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function payment_method_entity_methods_work_correctly(): void
    {
        // Test método isExpired() con tarjeta expirada
        $expiredMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_expired',
            brand: 'Visa',
            last4: '4444',
            exp_month: 01,
            exp_year: 2020, // Año pasado
            id: 3
        );

        $this->assertTrue($expiredMethod->isExpired());

        // Test método isExpired() con tarjeta vigente
        $futureYear = date('Y') + 2;
        $validMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_valid',
            brand: 'Mastercard',
            last4: '5555',
            exp_month: 12,
            exp_year: (int) $futureYear,
            id: 4
        );

        $this->assertFalse($validMethod->isExpired());

        // Test método expirationDate()
        $this->assertEquals('01/20', $expiredMethod->expirationDate());
        $this->assertEquals('12/' . substr($futureYear, -2), $validMethod->expirationDate());

        // Test método getMaskedCard()
        $this->assertEquals('**** **** **** 4444', $expiredMethod->getMaskedCard());
        $this->assertEquals('**** **** **** 5555', $validMethod->getMaskedCard());
    }

    #[Test]
    public function methods_have_correct_signatures(): void
    {
        $this->assertMethodParameterType('findById', 'int');
        $this->assertMethodParameterType('findByStripeId', 'string');
        $this->assertMethodParameterType('findByStripeIds', 'array');
        $this->assertMethodParameterType('getByUserId', 'int');

        $this->assertMethodReturnType('findById', PaymentMethod::class);
        $this->assertMethodReturnType('findByStripeId', PaymentMethod::class);
        $this->assertMethodReturnType('findByStripeIds', 'array');
        $this->assertMethodReturnType('getByUserId', 'array');
    }

    #[Test]
    public function payment_method_without_expiration_details(): void
    {
        $method = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_noexp',
            brand: null,
            last4: null,
            exp_month: null,
            exp_year: null,
            id: 5
        );

        $this->repository->setNextFindByIdResult($method);

        $result = $this->repository->findById(5);

        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertFalse($result->isExpired());
        $this->assertEquals('N/A', $result->expirationDate());
        $this->assertNull($result->getMaskedCard());
    }
}
