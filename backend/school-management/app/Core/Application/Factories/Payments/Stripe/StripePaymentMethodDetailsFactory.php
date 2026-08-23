<?php

namespace App\Core\Application\Factories\Payments\Stripe;

use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\CardPaymentMethodDetails;
use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\GenericPaymentMethodDetails;
use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\OxxoPaymentMethodDetails;
use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\PaymentMethodDetails;
use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\SpeiPaymentMethodDetails;

final class StripePaymentMethodDetailsFactory
{
    public static function fromStripe($details): PaymentMethodDetails
    {
        if (!$details) {
            return new GenericPaymentMethodDetails(null);
        }

        return match ($details->type ?? null) {
            'card' => self::card($details),
            'oxxo' => self::oxxo($details),
            'customer_balance' => self::spei($details),
            default => self::generic($details),
        };
    }

    public static function fromStripeString(string $type): string
    {
        return match ($type) {
            'card' => 'Tarjeta',
            'oxxo' => 'Oxxo',
            'customer_balance' => 'SPEI',
        };
    }

    public static function fromArray(array $data): PaymentMethodDetails
    {
        return match ($data['type'] ?? null) {
            'tarjeta' => new CardPaymentMethodDetails(
                brand: $data['brand'] ?? '',
                last4: $data['last4'] ?? '',
                funding: $data['funding'] ?? null,
            ),

            'oxxo' => new OxxoPaymentMethodDetails(
                reference: $data['reference'] ?? null,
                expiresAfter: $data['expires_after'] ?? null,
            ),

            'spei' => new SpeiPaymentMethodDetails(
                bankName: $data['bank_name'] ?? null,
                clabe: $data['clabe'] ?? null,
                reference: $data['reference'] ?? null,
            ),

            default => new GenericPaymentMethodDetails(
                type: $data['type'] ?? null,
            ),
        };
    }

    public static function card($details): PaymentMethodDetails
    {
        return new CardPaymentMethodDetails(
            brand: $details->card->brand,
            last4: $details->card->last4,
            funding: $details->card->funding ?? null,
        );
    }

    public static function oxxo($details): PaymentMethodDetails
    {
        return new OxxoPaymentMethodDetails(
            reference: $details->oxxo->number ?? null,
            expiresAfter: $details->oxxo->expires_after ?? null,
        );
    }

    public static function spei($details): PaymentMethodDetails
    {
        if(isset($details->customer_balance))
        {
            $bank = $details->customer_balance->bank_transfer ?? null;

            if ($bank && ($bank->type ?? null) === 'mx_bank_transfer') {
                return new SpeiPaymentMethodDetails(
                    bankName: $bank->bank_name ?? null,
                    clabe: $bank->clabe ?? null,
                    reference: $bank->reference ?? null,
                );
            }
        }
        return new SpeiPaymentMethodDetails(
            bankName: 'Desconocido',
            clabe: 'Desconocido',
            reference: 'Desconocido',
        );
    }

    public static function generic($details): PaymentMethodDetails
    {
        return new GenericPaymentMethodDetails(
            type: $details->type ?? null,
        );
    }

}
