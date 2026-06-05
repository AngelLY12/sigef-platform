<?php

namespace App\Core\Domain\Enum\Notification;

/**
 * @OA\Schema(
 *     schema="NotificationType",
 *     type="string",
 *     enum={
 *         "import_error",
 *         "import_finished",
 *         "invitation_accepted",
 *         "invitation_failed",
 *         "relation_deleted",
 *         "payment_concept_status_changed",
 *         "payment_concept_changed",
 *         "promotion_completed",
 *         "promotion_failed"
 *     }
 * )
 */
enum NotificationType: string
{
    case IMPORT_ERROR = 'import_error';
    case IMPORT_FINISHED = 'import_finished';

    case INVITATION_ACCEPTED = 'invitation_accepted';
    case INVITATION_FAILED = 'invitation_failed';

    case RELATION_DELETED = 'relation_deleted';

    case PAYMENT_CONCEPT_STATUS_CHANGED = 'payment_concept_status_changed';
    case PAYMENT_CONCEPT_CHANGED = 'payment_concept_changed';

    case PROMOTION_COMPLETED = 'promotion_completed';
    case PROMOTION_FAILED = 'promotion_failed';

}
