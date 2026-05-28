<?php

namespace App\Core\Application\Traits;

use App\Core\Application\Mappers\MailMapper;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Core\Domain\Utils\Helpers\Money;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\DomainException;
use App\Exceptions\NotFound\PaymentNotFountException;
use App\Jobs\ClearStudentCacheJob;
use App\Jobs\SendMailJob;
use App\Mail\PaymentCreatedMail;
use Illuminate\Support\Facades\DB;

trait HasPaymentSession
{
    private const TAG_CARDS = [CachePrefix::STUDENT->value, StudentCacheSufix::CARDS->value];
    public function __construct(
        private UserQueryRepInterface $userRepo,
        private PaymentRepInterface $paymentRepo,
        private PaymentMethodRepInterface $pmRepo,
        private StripeGatewayQueryInterface $stripe,
        private PaymentQueryRepInterface $pqRepo,
        private PaymentEventRepInterface $paymentEventRep,
        private PaymentEventQueryRepInterface $paymentEventQueryRep,
        private CacheService $service

    ) {

    }
    public function handlePaymentSession($session, array $fields, string $eventId, PaymentEventType $eventType): ?Payment
    {
        $payment = $this->pqRepo->findBySessionId($session->id);
        if (!$payment) {
            logger()->warning("No se encontró el pago con session_id={$session->id}");
            throw new PaymentNotFountException();
        }

        $user = $this->userRepo->getUserByStripeCustomer($session->customer);

        $status = $fields['status'];

        $event = $this->createPaymentEvent(
            $payment,
            $eventType,
            $session,
            $eventId,
            $fields,
        );

        if ($event->processed) {
            logger()->info("PaymentEvent ya procesado: {$event->id} para session {$session->id}");
            return $payment;
        }

        try {
            if ($status === PaymentStatus::PAID) {
                $received = Money::from($session->amount_subtotal)->divide('100');
                $amountReceived = $payment->amount_received
                    ? Money::from($payment->amount_received)->add($received)
                    : $received;

                $expected = Money::from($payment->amount);

                $fields['amount_received'] = $amountReceived->finalize();
                $fields['status'] = $amountReceived->isLessThan($expected)
                    ? PaymentStatus::UNDERPAID
                    : PaymentStatus::PAID;
            }
            $payment = $this->paymentRepo->update($payment->id, $fields);

            if ($payment->status === PaymentStatus::PAID
                || $payment->status === PaymentStatus::UNDERPAID) {
                $this->sendPaymentEmail($payment,$user, $eventId);
            }
            $this->paymentEventRep->update($event->id,
                [
                    'processed' => true,
                    'processed_at' => now(),
                    'status' => $payment->status,
                    'amount_received' => $payment->amount_received,
                    'metadata' => array_merge($event->metadata ?? [], [
                        'email_sent' => true,
                        'user_id' => $user->id
                    ])
                ]);
            ClearStudentCacheJob::dispatch($user->id)->onQueue('cache');

        }
        catch (\Exception $e) {
            $this->paymentEventRep->update($event->id, [
                'error_message' => $e->getMessage(),
                'retry_count' => ($event->retryCount ?? 0) + 1,
            ]);

            throw $e;
        }
        return $payment;
    }

    public function finalizeSetupSession($obj)
    {
        if(empty($obj->customer))
        {
            logger()->error("Checkout session {$obj->id} sin customer");
            return false;
        }
        try {
            $user = $this->userRepo->getUserByStripeCustomer($obj->customer);

            $setupIntent = $this->stripe->getSetupIntentFromSession($obj->id);
            $pm = $this->stripe->retrievePaymentMethod($setupIntent->payment_method);

            $paymentMethod = new PaymentMethod(
                user_id: $user->id,
                stripe_payment_method_id: $pm->id,
                brand: $pm->card->brand ?? null,
                last4: $pm->card->last4 ?? null,
                exp_month: $pm->card->exp_month ?? null,
                exp_year: $pm->card->exp_year ?? null
            );

            $pm= DB::transaction(function() use ($paymentMethod) {
                return $this->pmRepo->create($paymentMethod);
            });
            $this->service->flushTags(array_merge(self::TAG_CARDS, ["userId:{$user->id}"]));
            return true;
        } catch (DomainException $e) {
            logger()->warning("Excepción de dominio en webhook: " . $e->getMessage(), [
                'exception' => get_class($e),
                'use_case' => static::class
            ]);
            return false;

        } catch (\Illuminate\Validation\ValidationException $e) {
            logger()->warning("Excepción de validación en webhook: " . $e->getMessage());
            return false;

        } catch (\Exception $e) {
            logger()->error("Error inesperado en webhook: " . $e->getMessage(), [
                'exception' => get_class($e),
                'use_case' => static::class,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

    }

    private function createPaymentEvent(
        Payment $payment,
        PaymentEventType $eventType,
        $session,
        string $eventId,
        array $fields = [],
    ): PaymentEvent
    {
        $existing = $this->paymentEventQueryRep->findByStripeEvent($eventId, $eventType);
        if($existing)
        {
            return $existing;
        }

        $amount = '0.00';
        if(isset($session->amount_subtotal) && $session->amount_subtotal > 0) {
            $amount = Money::from($session->amount_total)->divide('100')->finalize();
        }

        $event = PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $session->payment_intent,
            sessionId: $session->id,
            amount: $amount,
            eventType: $eventType,
            metadata: [
                'raw_session' => $session,
                'fields' => $fields,
                'stripe_event_type' => $this->getStripeEventType($eventType),
                'email_type' => 'payment_created'
            ],
        );

        return $this->paymentEventRep->create($event);
    }

    private function sendPaymentEmail(Payment $payment, User $user, string $eventId): void
    {
        $emailEvent = $this->createEmailEvent($payment, $user, $eventId);
        $data = MailMapper::toPaymentCreatedEmailDTO($payment, $user->fullName(), $user->email);
        $mail = new PaymentCreatedMail($data);
        SendMailJob::forUser($mail, $user->email, 'stripe_session', $emailEvent->id)->onQueue('emails');
    }

    private function createEmailEvent(Payment $payment, $user, string $eventId): PaymentEvent
    {
        $emailEvent = PaymentEvent::createEmailEvent(
            paymentId: $payment->id,
            eventId: $eventId,
            paymentIntentId: $payment->payment_intent_id,
            sessionId: $payment->stripe_session_id,
            eventType: PaymentEventType::EMAIL_PAYMENT_CREATED,
            recipientEmail: $user->email,
            emailData: [
                'email_template' => 'payment_created',
                'concept_name' => $payment->concept_name,
                'amount' => $payment->amount,
                'amount_received' => $payment->amount_received,
            ]
        );
        return $this->paymentEventRep->create($emailEvent);
    }

    private function getStripeEventType(PaymentEventType $eventType): string
    {
        return match($eventType) {
            PaymentEventType::WEBHOOK_SESSION_COMPLETED => 'checkout.session.completed',
            PaymentEventType::WEBHOOK_SESSION_ASYNC_COMPLETED => 'checkout.session.async_payment_succeeded',
            default => 'unknown'
        };
    }

}
