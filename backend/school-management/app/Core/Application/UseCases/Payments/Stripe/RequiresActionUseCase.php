<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\Mappers\EnumMapper;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Utils\Helpers\Money;
use App\Exceptions\DomainException;
use App\Exceptions\NotFound\PaymentNotFountException;
use App\Jobs\ClearStudentCacheJob;
use App\Jobs\SendMailJob;
use App\Mail\RequiresActionMail;

class RequiresActionUseCase
{
    public function __construct(
        private UserQueryRepInterface $userRepo,
        private PaymentEventQueryRepInterface $paymentEventQueryRep,
        private PaymentEventRepInterface $paymentEventRep,

    ) {
    }
    public function execute($obj, string $eventId){
        $eventMail = $this->paymentEventQueryRep->findByStripeEvent($eventId,PaymentEventType::EMAIL_REQUIRES_ACTION );
        if($eventMail && $eventMail->processed)
        {
            logger("El evento ya fue procesado y se envio el email");
            return true;
        }
        $user = $this->userRepo->getUserByStripeCustomer($obj->customer);

        try {
            $data = null;
            $sendMail = false;
            $nextAction = null;
            if (in_array('oxxo', $obj->payment_method_types ?? [])) {
                $nextAction = $obj->next_action ?? null;
                if ($nextAction) {
                    $data = $this->prepareDataForEmail($user, $obj, $nextAction);
                    $sendMail = true;
                }
            }

            if (in_array('customer_balance', $obj->payment_method_types ?? [])) {
                $nextAction = $obj->next_action ?? null;

                if ($nextAction) {
                    $data = $this->prepareDataForEmail($user, $obj, $nextAction);
                    $sendMail = true;
                }
            }

            if ($sendMail && $data) {
                $this->sendRequiresActionMail($data, $user, $eventId);
            }

            return true;
        } catch (\Exception $e) {
            if (!($e instanceof DomainException) && !($e instanceof \Illuminate\Validation\ValidationException)) {
                throw $e;
            }

            logger()->warning("Error procesando evento requires_action: " . $e->getMessage(), [
                'exception' => get_class($e),
                'event_id' => $eventId,
                'payment_intent_id' => $obj->id
            ]);

            return false;
        }
    }

    private function prepareDataForEmail(User $user, $obj, $nextAction): array
    {
        $data = [
            'recipientName' => $user->fullName(),
            'recipientEmail' => $user->email,
            'amount' => $obj->amount,

        ];
        if (isset($nextAction->oxxo_display_details)) {
            $data['next_action'] = [
                'type' => 'oxxo',
                'reference' => $nextAction->oxxo_display_details->number,
                'url' => $nextAction->oxxo_display_details->hosted_voucher_url,
            ];
            $data['payment_method_options'] = [
                'expires_after_days' => $obj->payment_method_options->oxxo->expires_after_days ?? null
            ];
        }

        if (isset($nextAction->display_bank_transfer_instructions)) {
            $data['next_action'] = [
                'type' => 'spei',
                'reference' => $nextAction->display_bank_transfer_instructions->reference,
                'url' => $nextAction->display_bank_transfer_instructions->hosted_instructions_url,
            ];
            $data['payment_method_options'] = [
                'expires_after_days' => null
            ];
        }
        return $data;
    }

    private function sendRequiresActionMail(array $data, User $user, string $eventId): void
    {
        $emailEvent = $this->createEmailEvent($data, $user, $eventId);
        $mail = new RequiresActionMail(MailMapper::toRequiresActionEmailDTO($data));
        SendMailJob::forUser($mail, $user->email, 'requires_action', $emailEvent->id)->onQueue('emails');
    }

    private function createEmailEvent(array $data, User $user, string $eventId): PaymentEvent
    {
        $emailEvent = PaymentEvent::createEmailEvent(
            paymentId: null,
            eventId: $eventId,
            paymentIntentId:  null,
            sessionId: null,
            eventType: PaymentEventType::EMAIL_REQUIRES_ACTION,
            recipientEmail: $user->email,
            emailData: [
                'email_template' => 'requires_action',
                'next_action_type' => $data['next_action_type'] ?? 'unknown',
            ]
        );

        return $this->paymentEventRep->create($emailEvent);
    }


}
