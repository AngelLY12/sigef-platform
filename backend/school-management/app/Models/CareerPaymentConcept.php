<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Career;
use App\Models\PaymentConcept;

class CareerPaymentConcept extends Model
{
    protected $table = 'career_payment_concept';

    use HasFactory;
    protected $fillable = [
        'career_id',
        'payment_concept_id'

    ];

    public function career(){
        return $this->belongsTo(Career::class);
    }
    public function paymentConcept(){
        return $this->belongsTo(PaymentConcept::class);
    }
}
