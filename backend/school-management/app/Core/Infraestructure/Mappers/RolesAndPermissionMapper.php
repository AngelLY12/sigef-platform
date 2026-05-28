<?php

namespace App\Core\Infraestructure\Mappers;

use App\Core\Domain\Entities\Permission as EntitiesPermission;
use App\Core\Domain\Entities\Role as EntitiesRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionMapper{

    public static function toRoleDomain(Role $role): EntitiesRole
    {
        return new EntitiesRole(
            id:$role->id,
            name:$role->name
        );
    }
    public static function toPermissionDomain(Permission $permission): EntitiesPermission
    {
        return new EntitiesPermission(
            id:$permission->id,
            name: $permission->name,
            type:$permission->type,
        );
    }
}
