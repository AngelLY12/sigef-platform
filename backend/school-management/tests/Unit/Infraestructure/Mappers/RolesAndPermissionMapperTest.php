<?php

namespace Tests\Unit\Infraestructure\Mappers;

use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Core\Domain\Entities\Permission as DomainPermission;
use App\Core\Domain\Entities\Role as DomainRole;
use App\Core\Infraestructure\Mappers\RolesAndPermissionMapper;
use Tests\TestCase;

class RolesAndPermissionMapperTest extends TestCase
{
    #[Test]
    public function it_maps_role_from_eloquent_to_domain(): void
    {
        // Arrange
        $eloquentRole = new SpatieRole();
        $eloquentRole->id = 1;
        $eloquentRole->name = 'admin';

        // Act
        $domainRole = RolesAndPermissionMapper::toRoleDomain($eloquentRole);

        // Assert
        $this->assertInstanceOf(DomainRole::class, $domainRole);
        $this->assertEquals(1, $domainRole->id);
        $this->assertEquals('admin', $domainRole->name);
    }

    #[Test]
    public function it_maps_role_with_different_names(): void
    {
        $roleNames = [
            'admin',
            'user',
            'teacher',
            'student',
            'parent',
            'super_admin',
        ];

        foreach ($roleNames as $index => $roleName) {
            $eloquentRole = new SpatieRole();
            $eloquentRole->id = $index + 1;
            $eloquentRole->name = $roleName;

            $domainRole = RolesAndPermissionMapper::toRoleDomain($eloquentRole);

            $this->assertEquals($index + 1, $domainRole->id);
            $this->assertEquals($roleName, $domainRole->name);
        }
    }

    #[Test]
    public function it_maps_permission_from_eloquent_to_domain_with_all_fields(): void
    {
        // Arrange
        $eloquentPermission = new SpatiePermission();
        $eloquentPermission->id = 1;
        $eloquentPermission->name = 'users.create';
        $eloquentPermission->type = 'write';
        $eloquentPermission->belongs_to = 'users';

        // Act
        $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);

        // Assert
        $this->assertInstanceOf(DomainPermission::class, $domainPermission);
        $this->assertEquals(1, $domainPermission->id);
        $this->assertEquals('users.create', $domainPermission->name);
        $this->assertEquals('write', $domainPermission->type);
        $this->assertEquals('users', $domainPermission->belongsTo);
    }

    #[Test]
    public function it_maps_permission_with_null_belongs_to_field(): void
    {
        // Arrange
        $eloquentPermission = new SpatiePermission();
        $eloquentPermission->id = 2;
        $eloquentPermission->name = 'system.backup';
        $eloquentPermission->type = 'admin';
        $eloquentPermission->belongs_to = null; // Campo opcional

        // Act
        $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);

        // Assert
        $this->assertEquals(2, $domainPermission->id);
        $this->assertEquals('system.backup', $domainPermission->name);
        $this->assertEquals('admin', $domainPermission->type);
        $this->assertNull($domainPermission->belongsTo);
    }

    #[Test]
    public function it_maps_permission_with_different_types(): void
    {
        $permissionTypes = [
            'read',
            'write',
            'delete',
            'admin',
            'execute',
            'manage',
        ];

        foreach ($permissionTypes as $index => $type) {
            $eloquentPermission = new SpatiePermission();
            $eloquentPermission->id = $index + 10;
            $eloquentPermission->name = "resource.{$type}";
            $eloquentPermission->type = $type;
            $eloquentPermission->belongs_to = 'resource';

            $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);

            $this->assertEquals($index + 10, $domainPermission->id);
            $this->assertEquals("resource.{$type}", $domainPermission->name);
            $this->assertEquals($type, $domainPermission->type);
            $this->assertEquals('resource', $domainPermission->belongsTo);
        }
    }

    #[Test]
    public function it_maps_permission_with_different_belongs_to_values(): void
    {
        $belongsToValues = [
            'users',
            'payments',
            'courses',
            'grades',
            'settings',
            null, // También prueba null
        ];

        foreach ($belongsToValues as $index => $belongsTo) {
            $eloquentPermission = new SpatiePermission();
            $eloquentPermission->id = $index + 20;
            $eloquentPermission->name = "{$belongsTo}.manage";
            $eloquentPermission->type = 'manage';
            $eloquentPermission->belongs_to = $belongsTo;

            $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);

            $this->assertEquals($index + 20, $domainPermission->id);
            $this->assertEquals("{$belongsTo}.manage", $domainPermission->name);
            $this->assertEquals('manage', $domainPermission->type);
            $this->assertEquals($belongsTo, $domainPermission->belongsTo);
        }
    }

    #[Test]
    public function it_maps_permission_names_correctly(): void
    {
        $permissionNames = [
            'users.create',
            'users.read',
            'users.update',
            'users.delete',
            'payments.process',
            'courses.enroll',
            'system.*',
            'reports.generate',
        ];

        foreach ($permissionNames as $index => $name) {
            $eloquentPermission = new SpatiePermission();
            $eloquentPermission->id = $index + 30;
            $eloquentPermission->name = $name;
            $eloquentPermission->type = 'custom';
            $eloquentPermission->belongs_to = explode('.', $name)[0] ?? null;

            $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);

            $this->assertEquals($index + 30, $domainPermission->id);
            $this->assertEquals($name, $domainPermission->name);
        }
    }

    #[Test]
    public function it_handles_permission_with_empty_belongs_to_string(): void
    {
        // Arrange
        $eloquentPermission = new SpatiePermission();
        $eloquentPermission->id = 50;
        $eloquentPermission->name = 'global.permission';
        $eloquentPermission->type = 'read';
        $eloquentPermission->belongs_to = ''; // String vacío

        // Act
        $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);

        // Assert
        $this->assertEquals(50, $domainPermission->id);
        $this->assertEquals('global.permission', $domainPermission->name);
        $this->assertEquals('read', $domainPermission->type);
        $this->assertEquals('', $domainPermission->belongsTo);
    }

    #[Test]
    public function it_maps_role_and_permission_separately(): void
    {
        // Test que verifica que los mappers no se mezclan
        $eloquentRole = new SpatieRole();
        $eloquentRole->id = 100;
        $eloquentRole->name = 'test_role';

        $eloquentPermission = new SpatiePermission();
        $eloquentPermission->id = 200;
        $eloquentPermission->name = 'test.permission';
        $eloquentPermission->type = 'test';
        $eloquentPermission->belongs_to = 'test';

        // Act
        $domainRole = RolesAndPermissionMapper::toRoleDomain($eloquentRole);
        $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);

        // Assert - Verifica que sean instancias diferentes
        $this->assertInstanceOf(DomainRole::class, $domainRole);
        $this->assertInstanceOf(DomainPermission::class, $domainPermission);

        $this->assertEquals(100, $domainRole->id);
        $this->assertEquals('test_role', $domainRole->name);

        $this->assertEquals(200, $domainPermission->id);
        $this->assertEquals('test.permission', $domainPermission->name);
        $this->assertEquals('test', $domainPermission->type);
        $this->assertEquals('test', $domainPermission->belongsTo);
    }

    #[Test]
    public function it_creates_readonly_entities(): void
    {
        // Verifica que las entidades de dominio tienen propiedades readonly
        $eloquentRole = new SpatieRole();
        $eloquentRole->id = 5;
        $eloquentRole->name = 'readonly_test';

        $eloquentPermission = new SpatiePermission();
        $eloquentPermission->id = 6;
        $eloquentPermission->name = 'readonly.permission';
        $eloquentPermission->type = 'read';
        $eloquentPermission->belongs_to = 'system';

        // Act
        $domainRole = RolesAndPermissionMapper::toRoleDomain($eloquentRole);
        $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);

        // Assert - Verifica propiedades públicas readonly
        $this->assertEquals(5, $domainRole->id);
        $this->assertEquals('readonly_test', $domainRole->name);

        $this->assertEquals(6, $domainPermission->id);
        $this->assertEquals('readonly.permission', $domainPermission->name);
        $this->assertEquals('read', $domainPermission->type);
        $this->assertEquals('system', $domainPermission->belongsTo);

        // Verifica que no se puedan modificar (opcional - usando reflection)
        $roleReflection = new \ReflectionClass($domainRole);
        $idProperty = $roleReflection->getProperty('id');
        $this->assertTrue($idProperty->isReadOnly());
    }

    #[Test]
    public function it_maps_ids_correctly_for_large_values(): void
    {
        $largeIds = [
            1000,
            9999,
            10000,
            2147483647, // Max int común en MySQL
        ];

        foreach ($largeIds as $index => $id) {
            // Role
            $eloquentRole = new SpatieRole();
            $eloquentRole->id = $id;
            $eloquentRole->name = "role_{$id}";

            $domainRole = RolesAndPermissionMapper::toRoleDomain($eloquentRole);
            $this->assertEquals($id, $domainRole->id);

            // Permission
            $eloquentPermission = new SpatiePermission();
            $eloquentPermission->id = $id + 1;
            $eloquentPermission->name = "permission_{$id}";
            $eloquentPermission->type = 'type';
            $eloquentPermission->belongs_to = 'system';

            $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);
            $this->assertEquals($id + 1, $domainPermission->id);
        }
    }

    #[Test]
    public function it_handles_special_characters_in_names(): void
    {
        $specialNames = [
            'user-manager',
            'payment_processor',
            'course.enrollment',
            'system:admin',
            'api/v1/access',
            'reports.*',
        ];

        foreach ($specialNames as $index => $name) {
            // Role
            $eloquentRole = new SpatieRole();
            $eloquentRole->id = $index + 1000;
            $eloquentRole->name = $name;

            $domainRole = RolesAndPermissionMapper::toRoleDomain($eloquentRole);
            $this->assertEquals($name, $domainRole->name);

            // Permission
            $eloquentPermission = new SpatiePermission();
            $eloquentPermission->id = $index + 2000;
            $eloquentPermission->name = "permission.{$name}";
            $eloquentPermission->type = 'special';
            $eloquentPermission->belongs_to = 'system';

            $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);
            $this->assertEquals("permission.{$name}", $domainPermission->name);
        }
    }

    #[Test]
    public function it_maps_multiple_entities_correctly(): void
    {
        // Test para mapear múltiples roles y permisos
        $rolesData = [
            ['id' => 1, 'name' => 'admin'],
            ['id' => 2, 'name' => 'user'],
            ['id' => 3, 'name' => 'moderator'],
        ];

        $permissionsData = [
            ['id' => 1, 'name' => 'create', 'type' => 'write', 'belongs_to' => 'users'],
            ['id' => 2, 'name' => 'read', 'type' => 'read', 'belongs_to' => 'users'],
            ['id' => 3, 'name' => 'update', 'type' => 'write', 'belongs_to' => 'users'],
            ['id' => 4, 'name' => 'delete', 'type' => 'write', 'belongs_to' => 'users'],
        ];

        $domainRoles = [];
        $domainPermissions = [];

        // Mapear roles
        foreach ($rolesData as $roleData) {
            $eloquentRole = new SpatieRole();
            $eloquentRole->id = $roleData['id'];
            $eloquentRole->name = $roleData['name'];

            $domainRoles[] = RolesAndPermissionMapper::toRoleDomain($eloquentRole);
        }

        // Mapear permisos
        foreach ($permissionsData as $permissionData) {
            $eloquentPermission = new SpatiePermission();
            $eloquentPermission->id = $permissionData['id'];
            $eloquentPermission->name = $permissionData['name'];
            $eloquentPermission->type = $permissionData['type'];
            $eloquentPermission->belongs_to = $permissionData['belongs_to'];

            $domainPermissions[] = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);
        }

        // Assert
        $this->assertCount(3, $domainRoles);
        $this->assertCount(4, $domainPermissions);

        // Verificar algunos valores específicos
        $this->assertEquals('admin', $domainRoles[0]->name);
        $this->assertEquals('user', $domainRoles[1]->name);

        $this->assertEquals('create', $domainPermissions[0]->name);
        $this->assertEquals('write', $domainPermissions[0]->type);
        $this->assertEquals('users', $domainPermissions[0]->belongsTo);
    }

    #[Test]
    public function it_uses_correct_field_names_in_mapping(): void
    {
        // Verifica que los nombres de campo se mapeen correctamente
        // especialmente importante para belongs_to (snake_case) vs belongsTo (camelCase)

        $eloquentPermission = new SpatiePermission();
        $eloquentPermission->id = 99;
        $eloquentPermission->name = 'test.mapping';
        $eloquentPermission->type = 'test';
        $eloquentPermission->belongs_to = 'test_module'; // snake_case en Eloquent

        $domainPermission = RolesAndPermissionMapper::toPermissionDomain($eloquentPermission);

        // El mapper debe convertir belongs_to a belongsTo (camelCase)
        $this->assertEquals('test_module', $domainPermission->belongsTo);

        // Verifica que no existe una propiedad snake_case
        $this->assertFalse(property_exists($domainPermission, 'belongs_to'));
    }

}
