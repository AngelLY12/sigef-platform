<?php

namespace App\Core\Application\Mappers;

use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserStatus;

class EnumMapper
{
    public static function fromStripe(string $stripeStatus): PaymentStatus
    {
            return match ($stripeStatus) {
                'paid', 'no_payment_required'  => PaymentStatus::PAID,
                'unpaid' =>PaymentStatus::UNPAID,
                'succeeded' => PaymentStatus::SUCCEEDED,
                'requires_action' => PaymentStatus::REQUIRES_ACTION,


            default => PaymentStatus::DEFAULT,
        };
    }
    public static function toPaymentConceptAppliesTo(string $appliesTo): PaymentConceptAppliesTo
    {
        return PaymentConceptAppliesTo::from(strtolower($appliesTo));
    }

    public static function toPaymentConceptStatus(string $status): PaymentConceptStatus
    {
        return PaymentConceptStatus::from(strtolower($status));
    }

    public static function toUserGender(string $gender): UserGender
    {
        return UserGender::from(strtolower($gender));
    }

    public static function toUserBloodType(string $bloodType): UserBloodType
    {
        return UserBloodType::from(strtoupper($bloodType));
    }

    public static function toUserStatus(string $status): UserStatus
    {
        return UserStatus::from(strtolower($status));
    }

}
