<?php

namespace App\Models;

use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    protected $table = 'payment_events';
    use HasFactory;
    protected $fillable =[
        'payment_id',
        'stripe_event_id',
        'stripe_payment_intent_id',
        'stripe_session_id',
        'event_type',
        'metadata',
        'amount_received',
        'processed',
        'error_message',
        'retry_count',
        'processed_at',
        'status',

    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'amount_received' => 'decimal:2',
            'processed' => 'boolean',
            'retry_count' => 'integer',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'status' => PaymentStatus::class,
            'event_type' => PaymentEventType::class,
        ];
    }

    public function payment(){
        return $this->belongsTo(Payment::class);
    }

    public function markAsProcessed(?string $status = null): self
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
            'status' => $status ?? $this->status,
        ]);
        return $this;
    }

    public function markAsFailed(string $error, int $maxRetries = 3): self
    {
        $newRetryCount = $this->retry_count + 1;
        $shouldMarkAsProcessed = $newRetryCount >= $maxRetries;

        $this->update([
            'error_message' => $error,
            'retry_count' => $newRetryCount,
            'processed' => $shouldMarkAsProcessed,
            'processed_at' => $shouldMarkAsProcessed ? now() : null,
        ]);

        return $this;
    }

    public function isProcessable(): bool
    {
        return !$this->processed && $this->retry_count < 3;
    }

    public function scopePending($query, int $maxRetries = 3)
    {
        return $query->where('processed', false)
            ->where('retry_count', '<', $maxRetries);
    }

    public function scopeForPayment($query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
