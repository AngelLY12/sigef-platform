<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Request\Mail\NewPaymentConceptEmailDTO;
use App\Core\Application\DTO\Request\Mail\NewUserCreatedEmailDTO;
use App\Core\Application\DTO\Request\Mail\PaymentCreatedEmailDTO;
use App\Core\Application\DTO\Request\Mail\PaymentFailedEmailDTO;
use App\Core\Application\DTO\Request\Mail\PaymentValidatedEmailDTO;
use App\Core\Application\DTO\Request\Mail\RequiresActionEmailDTO;
use App\Core\Application\DTO\Request\Mail\SendParentInviteEmailDTO;
use App\Core\Application\DTO\Response\User\UserRecipientDTO;
use App\Core\Application\Factories\Payments\Stripe\RequiredActionDetailsFactory;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Utils\Helpers\Money;
use Carbon\Carbon;
use Stripe\PaymentIntent;

class MailMapper
{
    public static function toPaymentCreatedEmailDTO(Payment $payment, string $recipientName, string $recipientEmail): PaymentCreatedEmailDTO
    {
        return new PaymentCreatedEmailDTO(
            recipientName: $recipientName,
            recipientEmail: $recipientEmail,
            concept_name: $payment->concept_name,
            amount: $payment->amount,
            created_at: Carbon::now()->toDateTimeString(),
            url: $payment->url,
            stripe_session_id: $payment->stripe_session_id
        );
    }

    public static function toNewPaymentConceptEmailDTO(UserRecipientDTO $user, PaymentConcept $concept): NewPaymentConceptEmailDTO
    {
        return new NewPaymentConceptEmailDTO(
            recipientName: $user->fullName,
            recipientEmail: $user->email,
            concept_name: $concept->concept_name,
            amount: $concept->amount,
            end_date: $concept->end_date?->format('d-m-Y'),
            start_date: $concept->start_date->format('d-m-Y'),
            isDisable: $concept->isDisable(),
        );
    }

    public static function toPaymentValidatedEmailDTO(User $user, Payment $payment): PaymentValidatedEmailDTO
    {
        return new PaymentValidatedEmailDTO(
            recipientName: $user->fullName(),
            recipientEmail: $user->email,
            concept_name: $payment->concept_name,
            amount: $payment->amount,
            amount_received:$payment->amount_received,
            status: $payment->status->value,
            payment_method_detail: $payment->payment_method_details ?? null,
            payment_intent_id: $payment->payment_intent_id,
            url:$payment->url ?? null,
        );
    }

    public static function toPaymentFailedEmailDTO(User $user, Payment $payment, string $error): PaymentFailedEmailDTO
    {
        return new PaymentFailedEmailDTO(
            recipientName: $user->fullName(),
            recipientEmail: $user->email,
            concept_name: $payment->concept_name,
            amount:$payment->amount,
            error: $error
        );
    }

    public static function toRequiresActionEmailDTO(User $user, PaymentIntent $paymentIntent): ?RequiresActionEmailDTO
    {
        $requiredAction = RequiredActionDetailsFactory::fromStripe(
            $paymentIntent
        );

        if (!$requiredAction) {
            return null;
        }
        return new RequiresActionEmailDTO(
            recipientName: $user->fullName(),
            recipientEmail: $user->email,
            amount: (string) $paymentIntent->amount,
            requiredActionDetails: $requiredAction,
        );
    }
    public static function toNewUserCreatedEmailDTO(string $fullName, string $email, string $password): NewUserCreatedEmailDTO
    {
        return new NewUserCreatedEmailDTO(
            recipientName: $fullName,
            recipientEmail: $email,
            password: $password
        );
    }

    public static function toSendParentInviteEmail(string $fullName, string $email, string $token): SendParentInviteEmailDTO
    {
        return new SendParentInviteEmailDTO(
            recipientName: $fullName,
            recipientEmail:$email,
            token:$token
        );
    }

}
