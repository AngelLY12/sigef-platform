<?php

namespace App\Models;

use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Status\EmailEventStatus;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Infraestructure\Casts\EmailEventMetadataCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'event_type',
        'recipient_email',
        'status',
        'source_type',
        'source_id',
        'attempt_count',
        'error_message',
        'sent_at',
        'delivered_at',
        'failed_at',
        'metadata',
    ];
    protected function casts(): array
    {
        return [
            'source_type' => EmailEventSourceType::class,
            'event_type' => EmailEventType::class,
            'status' => EmailEventStatus::class,
            'metadata' => EmailEventMetadataCast::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
