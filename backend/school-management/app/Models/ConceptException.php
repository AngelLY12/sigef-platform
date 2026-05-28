<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConceptException extends Model
{
    protected $table = 'concept_exceptions';
    use HasFactory;
    protected $fillable =[
        'payment_concept_id',
        'user_id'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function paymentConcept(){
        return $this->belongsTo(PaymentConcept::class);
    }
}
