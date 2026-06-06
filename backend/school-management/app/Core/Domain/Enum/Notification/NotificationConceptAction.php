<?php

namespace App\Core\Domain\Enum\Notification;

enum NotificationConceptAction: string
{
    case APPLIES_TO_CHANGED = 'applies_to_changed';
    case EXCEPTIONS_UPDATE = 'exceptions_update';
    case RELATION_UPDATE = 'relation_update';
    case RELATION_REMOVED = 'relation_removed';
    case CREATED_CONCEPT = 'created_concept';
    case FIELD_UPDATE ='field_update';
    case STATUS_UPDATE = 'status_update';
}
