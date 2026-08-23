<?php

namespace App\Models;

use App\Core\Domain\Enum\Events\ReconciliationOutcome;
use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\Enum\Events\Status\ReconciliationEventStatus;
use App\Core\Infraestructure\Casts\ReconciliationEventMetadataCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'outcome',
        'status',
        'source_type',
        'source_id',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => ReconciliationSourceType::class,
            'outcome' => ReconciliationOutcome::class,
            'status' => ReconciliationEventStatus::class,
            'metadata' => ReconciliationEventMetadataCast::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
