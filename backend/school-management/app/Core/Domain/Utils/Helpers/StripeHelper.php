<?php

namespace App\Core\Domain\Utils\Helpers;

class StripeHelper
{
    public static function amountFromCents(int $amount): string
    {
        return $amount > 0
            ? Money::from($amount)->divide('100')->finalize()
            : '0.00';
    }

}
