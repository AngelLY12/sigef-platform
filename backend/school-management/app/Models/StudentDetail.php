<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentDetail extends Model
{
    use LogsActivity, HasFactory;
    protected $table = 'student_details';
    protected $fillable = [
        'user_id',
        'career_id',
        'n_control',
        'semestre',
        'group',
        'workshop'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function career(){
        return $this->belongsTo(Career::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['career_id', 'n_control' ,'semestre'])
            ->logOnlyDirty()
            ->useLogName('studentDetail');
    }
}
