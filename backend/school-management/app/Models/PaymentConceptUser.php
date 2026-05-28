<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PaymentConcept;
use App\Models\User;

class PaymentConceptUser extends Model
{
    protected $table = 'payment_concept_user';
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
