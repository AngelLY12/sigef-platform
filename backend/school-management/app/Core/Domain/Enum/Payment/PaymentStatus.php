<?php

namespace App\Core\Domain\Enum\Payment;

/**
 * @OA\Schema(
 *     schema="PaymentStatus",
 *     type="string",
 *     description="Estatus válidos de un pago",
 *     enum={"succeeded", "requires_action", "paid", "unpaid", "pending", "overpaid", "underpaid"},
 *     example="paid"
 * )
 */
enum PaymentStatus: string
{
    //terminal
    case SUCCEEDED = 'succeeded';
    case OVERPAID = 'overpaid';
    case FAILED = 'failed';
    //Paid but nonTerminal
    case PAID = 'paid';
    //nonTerminal
    case REQUIRES_ACTION = 'requires_action';
    case UNPAID = 'unpaid';
    case DEFAULT = 'pending';
    case UNDERPAID = 'underpaid';


    public static function paidStatuses(): array
    {
        return [
            self::SUCCEEDED->value,
            self::OVERPAID->value,
            self::PAID->value,
        ];
    }

    public static function terminalStatuses(): array
    {
        return [
            self::SUCCEEDED->value,
            self::OVERPAID->value,
            self::FAILED->value,
        ];
    }

    public static function receivedStatuses(): array
    {
        return [
            self::SUCCEEDED->value,
            self::OVERPAID->value,
        ];
    }

    public static function nonTerminalStatuses(): array
    {
        return [
            self::DEFAULT->value,
            self::UNDERPAID->value,
            self::UNPAID->value,
            self::REQUIRES_ACTION->value,
        ];
    }

    public static function nonPaidStatuses(): array
    {
        return [
            self::DEFAULT,
            self::UNPAID,
            self::REQUIRES_ACTION,
        ];
    }

    public static function reconcilableStatuses(): array
    {
        return [
            self::DEFAULT,
            self::PAID,
            self::UNDERPAID
        ];
    }

}
