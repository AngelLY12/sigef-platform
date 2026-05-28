<?php

namespace App\Core\Domain\Enum\Payment;

enum PaymentEventType: string
{
    case WEBHOOK_PAYMENT_SUCCEEDED = 'webhook.payment_succeeded';
    case WEBHOOK_PAYMENT_FAILED = 'webhook.payment_failed';
    case WEBHOOK_PAYMENT_REQUIRES_ACTION = 'webhook.requires_action';
    case WEBHOOK_SESSION_EXPIRED = 'webhook.session_expired';
    case WEBHOOK_PAYMENT_CANCELLED = 'webhook.payment_cancelled';
    case WEBHOOK_SESSION_COMPLETED = 'webhook.session_completed';
    case WEBHOOK_SESSION_ASYNC_COMPLETED = 'webhook.session_async_completed';
    case WEBHOOK_PAYMENT_METHOD_ATTACHED = 'webhook.payment_method_attached';

    case RECONCILIATION_STARTED = 'reconciliation.started';
    case RECONCILIATION_COMPLETED = 'reconciliation.completed';
    case RECONCILIATION_FAILED = 'reconciliation.failed';
    case RECONCILIATION_BATCH_FAILED = 'reconciliation.batch_failed';
    case RECONCILIATION_BATCH_COMPLETED = 'reconciliation.batch_completed';

    case MANUAL_UPDATE = 'manual.update';
    case SYSTEM_CORRECTION = 'system.correction';

    case EMAIL_PAYMENT_CREATED = 'email.payment_created';
    case EMAIL_PAYMENT_VALIDATED = 'email.payment_validated';
    case EMAIL_PAYMENT_FAILED = 'email.payment_failed';
    case EMAIL_REQUIRES_ACTION = 'email.requires_action';

    // Helper methods
    public function isWebhook(): bool
    {
        return str_starts_with($this->value, 'webhook.');
    }

    public function isReconciliation(): bool
    {
        return str_starts_with($this->value, 'reconciliation.');
    }

    public static function webhookEvents(): array
    {
        return array_filter(
            self::cases(),
            fn($case) => $case->isWebhook()
        );
    }

    public function isEmail(): bool
    {
        return str_starts_with($this->value, 'email.');
    }

    public static function emailEvents(): array
    {
        return array_filter(
            self::cases(),
            fn($case) => $case->isEmail()
        );
    }

}
