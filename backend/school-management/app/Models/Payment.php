<?php

namespace App\Models;

use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Infraestructure\Mappers\PaymentMapper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\PaymentConcept;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'payment_concept_id',
        'payment_method_id',
        'stripe_payment_method_id',
        'concept_name',
        'amount',
        'amount_received',
        'payment_method_details',
        'status',
        'payment_intent_id',
        'url',
        'stripe_session_id'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function paymentConcept(){
        return $this->belongsTo(PaymentConcept::class);
    }
    public function paymentMethod(){
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentEvents()
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }


    public function toDomain(): \App\Core\Domain\Entities\Payment
    {
        return PaymentMapper::toDomain($this);
    }

    protected function casts(): array
    {   return [
            'payment_method_details' => 'array',
            'amount' => 'decimal:2',
            'amount_received' => 'decimal:2',
            'status' => PaymentStatus::class
        ];
    }


}
