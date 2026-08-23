<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\Factories\Emails\Events\EmailEventFactory;
use App\Core\Application\Factories\Payments\Stripe\StripePaymentMethodDetailsFactory;
use App\Core\Application\Factories\Payments\Stripe\WebhookChargeEventFactory;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Application\Services\Events\Contracts\PaymentEventManagerInterface;
use App\Core\Application\Traits\HasPaymentStripe;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Exceptions\NotFound\PaymentNotFountException;
use App\Exceptions\NotFound\UserNotFoundException;
use App\Jobs\ClearStaffCacheJob;
use App\Jobs\ClearStudentCacheJob;
use App\Mail\PaymentValidatedMail;
use Stripe\Charge;

class ChargeSucceededUseCase
{
    use HasPaymentStripe;

    public function __construct(
        private PaymentQueryRepInterface       $paymentQueryRep,
        private PaymentRepInterface            $paymentRep,
        private PaymentMethodQueryRepInterface $paymentMethodQueryRep,
        private UserQueryRepInterface $userQueryRep,
        private PaymentEventManagerInterface $paymentEventManager,
        private EmailEventManagerInterface $emailEventManager,
    )
    {
        $this->setRepository($this->paymentRep);
    }

    public function execute(string $eventId, Charge $charge): Payment
    {

        $payment = $this->paymentQueryRep->findById($charge->metadata->payment_id);
        if (!$payment) {
            throw new PaymentNotFountException();
        }
        $pm = $this->paymentMethodQueryRep->findByStripeId(
            $charge->payment_method
        );

        $userId = (int) $charge->metadata->user_id;

        $event = $this->paymentEventManager->findOrCreate(
            stripeEventId: $eventId,
            eventType: PaymentEventType::WEBHOOK_CHARGE_SUCCEEDED,
            factory: fn () => $this->createPaymentEvent(
                payment: $payment,
                charge: $charge,
                eventId: $eventId,
                pm: $pm
            )
        );
        if ($event->processed) {
            return $payment;
        }

        $this->paymentEventManager->process(
            event: $event,
            callback: function () use (
                $event,
                $payment,
                $charge,
                $eventId,
                $userId,
                $pm
            ) {
                $this->processCharge(
                    event: $event,
                    payment: $payment,
                    charge: $charge,
                    eventId: $eventId,
                    userId: $userId,
                    pm: $pm
                );
            }
        );


        return $payment;
    }

    private function processCharge(
        PaymentEvent $event,
        Payment $payment,
        Charge $charge,
        string $eventId,
        int $userId,
        ?PaymentMethod $pm = null
    ): void {

        $payment = $this->updatePaymentWithStripeData(
            $payment,
            $charge,
            $pm
        );

        $this->sendValidatedPaymentEmail(
            payment: $payment,
            userId: $userId,
            eventId: $eventId,
        );

        ClearStudentCacheJob::dispatch($userId)
            ->onQueue('cache');

        ClearStaffCacheJob::dispatch()
            ->onQueue('cache');

        $event->setStatus($payment->status);
        $event->setAmountReceived($payment->amount_received);
    }

    private function sendValidatedPaymentEmail(
        Payment $payment,
        int $userId,
        string $eventId,
    ): void {
        $user = $this->userQueryRep->findById($userId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        $emailEvent = $this->emailEventManager->findOrCreate(
            eventType: EmailEventType::PAYMENT_VALIDATED,
            sourceType: EmailEventSourceType::STRIPE,
            sourceId: $eventId,
            factory: fn () => EmailEventFactory::paymentValidated(
                payment: $payment,
                user: $user,
                stripeEventId: $eventId,
            )
        );

        $mail = new PaymentValidatedMail(MailMapper::
        toPaymentValidatedEmailDTO(user: $user,payment: $payment));

        $this->emailEventManager->dispatch(
            event: $emailEvent,
            mail: $mail,
            recipientEmail: $user->email,
            jobType: 'single_reconcile_payment',
        );
    }

    private function createPaymentEvent(
        Payment $payment,
        Charge $charge,
        string $eventId,
        ?PaymentMethod $pm = null,
    ): PaymentEvent
    {
        $paymentMethodDetails = StripePaymentMethodDetailsFactory::fromStripe(
            $charge->payment_method_details
        );

        return WebhookChargeEventFactory::succeeded(eventId: $eventId, payment: $payment, charge: $charge, paymentMethodId: $pm?->id, paymentMethodDetails: $paymentMethodDetails);
    }
}
