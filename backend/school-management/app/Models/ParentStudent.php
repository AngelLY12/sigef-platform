<?php

namespace App\Models;

use App\Core\Domain\Enum\User\RelationshipType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class ParentStudent extends Model
{
    use HasFactory;
    protected $table = 'parent_student';

    protected $fillable = [
        'parent_id',
        'student_id',
        'parent_role_id',
        'student_role_id',
        'relationship',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function parentRole()
    {
        return $this->belongsTo(Role::class, 'parent_role_id');
    }

    public function studentRole()
    {
        return $this->belongsTo(Role::class, 'student_role_id');
    }

    protected function casts(): array
    {
        return [
            'relationship' => RelationshipType::class
        ];
    }

}
