<?php

namespace App\Core\Domain\Utils\Helpers;

use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use Illuminate\Support\Str;

final class EventSourceId
{
    public static function generateOperationId(): string
    {
        return Str::uuid()->toString();
    }

    public static function reconciliation(
        ReconciliationSourceType $sourceType,
        string $operationId,
    ): string {
        return sprintf(
            'evt_recon_%s_%s',
            $sourceType->value,
            $operationId
        );
    }

    public static function email(
        EmailEventSourceType $sourceType,
        EmailEventType $eventType,
        string $operationId,
        int $recipientId,
    ): string {
        return sprintf(
            'evt_email_%s_%s_%s_%s',
            $sourceType->value,
            $eventType->value,
            $operationId,
            $recipientId
        );
    }

}
