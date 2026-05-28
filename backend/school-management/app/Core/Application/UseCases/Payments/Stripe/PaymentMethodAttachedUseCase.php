<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class PaymentMethodAttachedUseCase
{
    private const TAG_CARDS = [CachePrefix::STUDENT->value, StudentCacheSufix::CARDS->value];
    public function __construct(
        private PaymentMethodRepInterface $pmRepo,
        private PaymentMethodQueryRepInterface $pmqRepo,
        private UserQueryRepInterface $userRepo,
        private PaymentEventRepInterface $paymentEventRep,
        private PaymentEventQueryRepInterface $paymentEventQueryRep,
        private CacheService $service

    ) {

    }
    public function execute($obj, string $eventId){

        if (!$obj) {
            logger()->error("PaymentMethod no encontrado: objeto nulo");
            return false;
        }
        $user = $this->userRepo->getUserByStripeCustomer($obj->customer);

        $paymentMethodId = $obj->id;
        $pm = $this->pmqRepo->existsPaymentMethodByStripeId($paymentMethodId);
        if ($pm) {
            logger()->info("El método de pago {$paymentMethodId} ya existe");
            $this->createPaymentEvent($obj, $user->id, $eventId, true);
            return true;
        }

        $event = $this->createPaymentEvent($obj, $user->id, $eventId, false);
        if ($event->processed) {
            logger()->info("PaymentEvent ya procesado: {$event->id} para payment_method {$paymentMethodId}");
            return true;
        }
        try {

            $pmDomain = new PaymentMethod(
                user_id: $user->id,
                stripe_payment_method_id: $paymentMethodId,
                brand: $obj->card->brand,
                last4: $obj->card->last4,
                exp_month: $obj->card->exp_month,
                exp_year: $obj->card->exp_year,
            );
            DB::transaction(function () use ($pmDomain) {
                $this->pmRepo->create($pmDomain);

            });
            $this->service->flushTags(array_merge(self::TAG_CARDS, ["userId:{$user->id}"]));
            $this->paymentEventRep->update($event->id, [
                'processed' => true,
                'processed_at' => now(),
                'metadata' => array_merge($event->metadata ?? [], [
                    'payment_method_created' => true,
                ])
            ]);
            return true;

        } catch (\Exception $e) {
            $this->paymentEventRep->update($event->id, [
                'error_message' => $e->getMessage(),
                'retry_count' => ($event->retryCount ?? 0) + 1,
                'metadata' => array_merge($event->metadata ?? [], [
                    'failed_at' => now()->toISOString(),
                    'error_class' => get_class($e)
                ])
            ]);

            if (!($e instanceof DomainException) && !($e instanceof \Illuminate\Validation\ValidationException)) {
                throw $e;
            }

            logger()->warning("Error procesando payment_method.attached: " . $e->getMessage(), [
                'exception' => get_class($e),
                'event_id' => $eventId,
                'payment_method_id' => $paymentMethodId
            ]);

            return false;
        }
    }

    private function createPaymentEvent($obj, int $userId, string $eventId, bool $alreadyExists = false): PaymentEvent
    {
        $paymentMethodId = $obj->id;

        $existing = $this->paymentEventQueryRep->findByStripeEvent(
            $eventId,
            PaymentEventType::WEBHOOK_PAYMENT_METHOD_ATTACHED
        );

        if ($existing) {
            return $existing;
        }

        $event = PaymentEvent::createWebhookEvent(
            paymentId: null,
            stripeEventId: $eventId,
            paymentIntentId: null,
            sessionId: null,
            amount: null,
            eventType: PaymentEventType::WEBHOOK_PAYMENT_METHOD_ATTACHED,
            metadata: [
                'raw_object' => $obj,
                'stripe_event_type' => 'payment_method.attached',
                'payment_method_id' => $paymentMethodId,
                'user_id' => $userId,
                'already_exists' => $alreadyExists,
                'customer_id' => $obj->customer ?? null
            ],
        );

        if ($alreadyExists) {
            $event->setProccessed(true);
            $event->setProcessedAt(now());
        }

        return $this->paymentEventRep->create($event);
    }


}
