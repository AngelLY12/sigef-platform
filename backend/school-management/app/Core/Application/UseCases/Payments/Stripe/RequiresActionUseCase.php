<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\DTO\Request\Mail\RequiresActionEmailDTO;
use App\Core\Application\Factories\Emails\Events\EmailEventFactory;
use App\Core\Application\Factories\Payments\Stripe\WebhookPaymentIntentEventFactory;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Application\Services\Events\Contracts\PaymentEventManagerInterface;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Exceptions\DomainException;
use App\Mail\RequiresActionMail;
use Stripe\PaymentIntent;

class RequiresActionUseCase
{
    public function __construct(
        private UserQueryRepInterface $userRepo,
        private PaymentQueryRepInterface $paymentQueryRep,
        private PaymentEventManagerInterface $paymentEventManager,
        private EmailEventManagerInterface $emailEventManager,

    ) {
    }
    public function execute(PaymentIntent $obj, string $eventId){
        $payment = $this->paymentQueryRep->findById($obj->metadata->payment_id);

        if (!$payment) {
            return false;
        }

        $paymentEvent = $this->paymentEventManager->findOrCreate(
            stripeEventId: $eventId,
            eventType: PaymentEventType::WEBHOOK_PAYMENT_REQUIRES_ACTION,
            factory: fn () => WebhookPaymentIntentEventFactory::requiresAction(
                eventId: $eventId,
                payment: $payment,
                paymentIntent: $obj,
            ),
        );

        if ($paymentEvent->processed) {
            return true;
        }

        try {
            $this->paymentEventManager->process(
                event: $paymentEvent,
                callback: fn () => $this->processRequiresAction(
                    payment: $payment,
                    paymentIntent: $obj,
                    eventId: $eventId,
                ),
            );
            return true;
        } catch (\Exception $e) {
            if (!($e instanceof DomainException) && !($e instanceof \Illuminate\Validation\ValidationException)) {
                throw $e;
            }

            return false;
        }
    }

    private function processRequiresAction(
        Payment $payment,
        PaymentIntent $paymentIntent,
        string $eventId,
    ): void {
        $user = $this->userRepo->getUserByStripeCustomer(
            $paymentIntent->customer
        );

        $data = MailMapper::toRequiresActionEmailDTO(user: $user,paymentIntent: $paymentIntent);

        if (!$data) {
            return;
        }

        $this->sendRequiresActionMail(
            data: $data,
            payment: $payment,
            user: $user,
            eventId: $eventId,
        );
    }

    private function sendRequiresActionMail(
        RequiresActionEmailDTO $data,
        Payment $payment,
        User $user,
        string $eventId,
    ): void {
        $emailEvent = $this->emailEventManager->findOrCreate(
            eventType: EmailEventType::PAYMENT_REQUIRES_ACTION,
            sourceType: EmailEventSourceType::STRIPE,
            sourceId: $eventId,
            factory: fn () => EmailEventFactory::paymentRequiresAction(
                payment: $payment,
                user: $user,
                stripeEventId: $eventId,
                requiredAction: $data->requiredActionDetails
            ),
        );

        $mail = new RequiresActionMail($data);

        $this->emailEventManager->dispatch(
            event: $emailEvent,
            mail: $mail,
            recipientEmail: $user->email,
            jobType: 'requires_action',
        );
    }
}
