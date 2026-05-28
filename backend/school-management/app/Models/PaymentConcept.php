<?php

namespace App\Models;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Career;
use App\Models\User;
use App\Models\Payment;
use App\Models\PaymentConceptSemester;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentConcept extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'concept_name',
        'description',
        'status',
        'start_date',
        'end_date',
        'amount',
        'applies_to',
        'mark_as_deleted_at'
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' =>  'date',
            'mark_as_deleted_at' => 'datetime',
            'amount' => 'decimal:2',
            'status' => PaymentConceptStatus::class,
            'applies_to' => PaymentConceptAppliesTo::class
        ];
    }

    public function careers(){
        return $this->belongsToMany(Career::class)->withTimestamps();
    }

    public function users(){
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function payments(){
        return $this->hasMany(Payment::class);
    }

    public function paymentConceptSemesters(){
        return $this->hasMany(PaymentConceptSemester::class);
    }

    public function exceptions()
    {
        return $this->belongsToMany(
            User::class,
            'concept_exceptions',
            'payment_concept_id',
            'user_id'
        )->withTimestamps()
            ->withPivot('user_id');
    }

    public function applicantTypes()
    {
        return $this->hasMany(PaymentConceptApplicantTag::class, 'payment_concept_id');
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['concept_name', 'description' ,'status', 'amount'])
            ->logOnlyDirty()
            ->useLogName('paymentConcept');
    }

}
