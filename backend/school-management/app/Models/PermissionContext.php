<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class PermissionContext extends Model
{
    protected $table = 'permission_contexts';

    protected $fillable = [
        'permission_id',
        'context',
        'target_role',
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where('target_role', $role);
    }

    public function scopeForContext($query, string $context)
    {
        return $query->where('context', $context);
    }
}
