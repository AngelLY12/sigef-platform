<?php

namespace App\Core\Application\Factories\Payments\Stripe;

use App\Core\Domain\ValueObjects\Payment\Stripe\RequiredActionDetails;
use Stripe\PaymentIntent;

final class RequiredActionDetailsFactory
{
    public static function fromStripe(PaymentIntent $paymentIntent): ?RequiredActionDetails
    {
        $nextAction = $paymentIntent->next_action;

        if (!$nextAction) {
            return null;
        }

        if (isset($nextAction->oxxo_display_details)) {
            return new RequiredActionDetails(
                type: 'oxxo',
                reference: $nextAction->oxxo_display_details->number,
                url: $nextAction->oxxo_display_details->hosted_voucher_url,
                expiresAfterDays:
                $paymentIntent->payment_method_options
                    ->oxxo
                    ->expires_after_days ?? null,
            );
        }

        if (isset($nextAction->display_bank_transfer_instructions)) {
            return new RequiredActionDetails(
                type: 'spei',
                reference: $nextAction->display_bank_transfer_instructions->reference,
                url: $nextAction->display_bank_transfer_instructions->hosted_instructions_url,
                expiresAfterDays: null,
            );
        }

        return null;
    }
}
