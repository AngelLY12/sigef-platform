<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Stripe;

use App\Core\Application\UseCases\Payments\Stripe\HandleFailedOrExpiredPaymentUseCase;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;
use App\Jobs\ClearStudentCacheJob;
use App\Jobs\SendMailJob;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class HandleFailedOrExpiredPaymentUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    #[Test]
    public function it_returns_false_when_payment_is_not_found(): void
    {
        $useCase = app(HandleFailedOrExpiredPaymentUseCase::class);

        $stripeObject = (object)[
            'id' => 'pi_not_found',
            'customer' => 'cus_123'
        ];

        $result = $useCase->execute(
            $stripeObject,
            'payment_intent.payment_failed',
            'evt_123'
        );

        $this->assertFalse($result);
    }

    #[Test]
    public function it_expires_session_when_payment_has_partial_amount(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'stripe_customer_id' => 'cus_partial_123',
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'stripe_session_id' => 'cs_expired_123',
            'amount_received' => 2500,
            'status' => PaymentStatus::UNPAID,
        ]);

        $stripeObject = (object)[
            'id' => 'cs_expired_123',
            'customer' => 'cus_partial_123',
        ];

        $stripeMock = Mockery::mock(StripeGatewayInterface::class);
        $stripeMock
            ->shouldReceive('expireSessionIfPending')
            ->with('cs_expired_123')
            ->once();

        $this->app->instance(StripeGatewayInterface::class, $stripeMock);

        $useCase = app(HandleFailedOrExpiredPaymentUseCase::class);

        $result = $useCase->execute(
            $stripeObject,
            'checkout.session.expired',
            'evt_expired_123'
        );

        $this->assertTrue($result);

        // El pago NO se elimina
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
        ]);

        // Evento procesado
        $this->assertDatabaseHas('payment_events', [
            'stripe_event_id' => 'evt_expired_123',
            'processed' => true,
        ]);

        Queue::assertPushed(ClearStudentCacheJob::class);
        Queue::assertPushed(SendMailJob::class);
    }

    #[Test]
    public function it_returns_true_if_event_was_already_processed(): void
    {
        $user = User::factory()->create();

        $payment = Payment::factory()->create([
            'payment_intent_id' => 'pi_done_123',
        ]);

        PaymentEvent::factory()->create([
            'payment_id' => $payment->id,
            'stripe_event_id' => 'evt_done_123',
            'processed' => true,
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_FAILED,
        ]);

        $stripeObject = (object)[
            'id' => 'pi_done_123',
            'customer' => 'cus_done_123',
        ];

        $useCase = app(HandleFailedOrExpiredPaymentUseCase::class);

        $result = $useCase->execute(
            $stripeObject,
            'payment_intent.payment_failed',
            'evt_done_123'
        );

        $this->assertTrue($result);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_returns_false_when_user_is_not_found(): void
    {
        $payment = Payment::factory()->create([
            'payment_intent_id' => 'pi_user_missing',
        ]);

        PaymentEvent::factory()->create([
            'payment_id' => $payment->id,
            'stripe_event_id' => 'evt_user_missing',
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_FAILED,
        ]);

        $this->mock(UserQueryRepInterface::class, function ($mock) {
            $mock->shouldReceive('getUserByStripeCustomer')
                ->andThrow(new \DomainException('User not found'));
        });

        $stripeObject = (object)[
            'id' => 'pi_user_missing',
            'customer' => 'cus_missing',
        ];

        $useCase = app(HandleFailedOrExpiredPaymentUseCase::class);

        $result = $useCase->execute(
            $stripeObject,
            'payment_intent.payment_failed',
            'evt_user_missing'
        );

        $this->assertFalse($result);
    }




}
