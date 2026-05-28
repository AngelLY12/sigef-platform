<?php

namespace Tests\Unit\Domain\Entities;

use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\Permission;
use PHPUnit\Framework\Attributes\Test;

class PermissionTest extends BaseDomainTestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $permission = new Permission(
            id: 1,
            name: 'create_user',
            type: 'action'
        );

        $this->assertInstanceOf(Permission::class, $permission);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_parameters()
    {
        $permission = new Permission(
            id: 5,
            name: 'edit_post',
            type: 'action',
        );

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals(5, $permission->id);
        $this->assertEquals('edit_post', $permission->name);
        $this->assertEquals('action', $permission->type);
    }

    #[Test]
    public function it_has_required_attributes()
    {
        $permission = new Permission(
            id: 10,
            name: 'delete_comment',
            type: 'action'
        );

        $this->assertEquals(10, $permission->id);
        $this->assertEquals('delete_comment', $permission->name);
        $this->assertEquals('action', $permission->type);
    }

    #[Test]
    public function it_accepts_valid_data()
    {
        $permission = new Permission(
            id: 15,
            name: 'view_dashboard',
            type: 'view',
        );

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals(15, $permission->id);
        $this->assertEquals('view_dashboard', $permission->name);
        $this->assertEquals('view', $permission->type);
    }

    #[Test]
    public function it_accepts_null_for_belongs_to()
    {
        $permission = new Permission(
            id: 20,
            name: 'system_admin',
            type: 'global',
        );

        $this->assertEquals('system_admin', $permission->name);
        $this->assertEquals('global', $permission->type);
    }

    #[Test]
    public function it_has_readonly_properties()
    {
        $permission = new Permission(
            id: 25,
            name: 'export_data',
            type: 'action'
        );

        $this->assertEquals(25, $permission->id);
        $this->assertEquals('export_data', $permission->name);
        $this->assertEquals('action', $permission->type);
    }

    #[Test]
    public function it_accepts_different_permission_names()
    {
        $names = [
            'create',
            'read',
            'update',
            'delete',
            'view_dashboard',
            'manage_users',
            'approve_posts',
            'moderate_comments',
            'access_reports'
        ];

        foreach ($names as $name) {
            $permission = new Permission(
                id: 1,
                name: $name,
                type: 'action'
            );

            $this->assertEquals($name, $permission->name);
        }
    }

    #[Test]
    public function it_accepts_different_permission_types()
    {
        $types = [
            'action',
            'view',
            'global',
            'module',
            'feature',
            'admin',
            'user',
            'system'
        ];

        foreach ($types as $type) {
            $permission = new Permission(
                id: 1,
                name: 'test_permission',
                type: $type
            );

            $this->assertEquals($type, $permission->type);
        }
    }

    #[Test]
    public function it_accepts_complex_permission_names()
    {
        $names = [
            'user.create',
            'post.update.own',
            'comment.delete.any',
            'report.view.financial',
            'settings.manage.general'
        ];

        foreach ($names as $name) {
            $permission = new Permission(
                id: 1,
                name: $name,
                type: 'action'
            );

            $this->assertEquals($name, $permission->name);
        }
    }

    #[Test]
    public function it_accepts_long_permission_names()
    {
        $longName = str_repeat('a', 100);
        $permission = new Permission(
            id: 1,
            name: $longName,
            type: 'action'
        );

        $this->assertEquals($longName, $permission->name);
    }

    #[Test]
    public function it_can_be_converted_to_json()
    {
        $permission = new Permission(
            id: 40,
            name: 'view_analytics',
            type: 'view',
        );

        $json = json_encode($permission);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(40, $decoded['id']);
        $this->assertEquals('view_analytics', $decoded['name']);
        $this->assertEquals('view', $decoded['type']);
    }

    #[Test]
    public function it_can_be_converted_to_json_with_null_belongs_to()
    {
        $permission = new Permission(
            id: 50,
            name: 'super_admin',
            type: 'global'
        );

        $json = json_encode($permission);
        $decoded = json_decode($json, true);

        $this->assertJson($json);
        $this->assertEquals(50, $decoded['id']);
        $this->assertEquals('super_admin', $decoded['name']);
        $this->assertEquals('global', $decoded['type']);
    }

    #[Test]
    public function it_returns_correct_json_structure()
    {
        $permission = new Permission(
            id: 60,
            name: 'edit_profile',
            type: 'action',
        );

        $json = json_encode($permission);
        $decoded = json_decode($json, true);

        $this->assertCount(3, $decoded);
        $this->assertArrayHasKey('id', $decoded);
        $this->assertArrayHasKey('name', $decoded);
        $this->assertArrayHasKey('type', $decoded);
    }

    #[Test]
    public function it_can_be_used_as_immutable_value_object()
    {
        $permission = new Permission(
            id: 70,
            name: 'publish_post',
            type: 'action',
        );

        $this->assertEquals(70, $permission->id);
        $this->assertEquals('publish_post', $permission->name);
        $this->assertEquals('action', $permission->type);

        $newPermission = new Permission(
            id: 70,
            name: 'publish_post',
            type: 'action',
        );

        $this->assertEquals($permission->id, $newPermission->id);
        $this->assertEquals($permission->name, $newPermission->name);
    }

    #[Test]
    public function it_can_be_compared_by_properties()
    {
        $permission1 = new Permission(
            id: 1,
            name: 'create',
            type: 'action',
        );

        $permission2 = new Permission(
            id: 1,
            name: 'create',
            type: 'action',
        );

        $permission3 = new Permission(
            id: 2,
            name: 'create',
            type: 'action',
        );

        $permission4 = new Permission(
            id: 1,
            name: 'update',
            type: 'action',
        );

        $this->assertEquals($permission1->id, $permission2->id);
        $this->assertEquals($permission1->name, $permission2->name);
        $this->assertEquals($permission1->type, $permission2->type);

        $this->assertNotEquals($permission1->id, $permission3->id);

        $this->assertNotEquals($permission1->name, $permission4->name);
    }

    #[Test]
    public function it_handles_edge_cases_for_names()
    {
        $edgeCases = [
            'a',
            str_repeat('b', 255),
            'permission-with-dashes',
            'permission_with_underscores',
            'permission.with.dots',
            '123_permission',
            'PermissionWithCaps',
            'p',
        ];

        foreach ($edgeCases as $name) {
            $permission = new Permission(
                id: 1,
                name: $name,
                type: 'action'
            );

            $this->assertEquals($name, $permission->name);
        }
    }

    #[Test]
    public function it_handles_edge_cases_for_types()
    {
        $edgeCases = [
            'a',
            'very_long_type_name_that_exceeds_normal_length',
            'type-with-dash',
            'type_with_underscore',
            '123type',
            'TypeCamelCase',
        ];

        foreach ($edgeCases as $type) {
            $permission = new Permission(
                id: 1,
                name: 'test',
                type: $type
            );

            $this->assertEquals($type, $permission->type);
        }
    }

    #[Test]
    public function it_provides_consistent_string_representation()
    {
        $permission = new Permission(
            id: 80,
            name: 'delete_user',
            type: 'danger',
        );
        $string = "Permission[id: 80, name: delete_user, type: danger, belongsTo: users]";

        $jsonString = json_encode($permission);
        $this->assertStringContainsString('"id":80', $jsonString);
        $this->assertStringContainsString('"name":"delete_user"', $jsonString);
        $this->assertStringContainsString('"type":"danger"', $jsonString);
    }

    #[Test]
    public function it_can_be_used_in_collections()
    {
        $permissions = [
            new Permission(id: 1, name: 'create', type: 'action'),
            new Permission(id: 2, name: 'read', type: 'action'),
            new Permission(id: 3, name: 'update', type: 'action'),
            new Permission(id: 4, name: 'delete', type: 'action'),
        ];

        $this->assertCount(4, $permissions);
        $this->assertInstanceOf(Permission::class, $permissions[0]);
        $this->assertInstanceOf(Permission::class, $permissions[1]);
        $this->assertInstanceOf(Permission::class, $permissions[2]);
        $this->assertInstanceOf(Permission::class, $permissions[3]);

        $this->assertEquals('create', $permissions[0]->name);
        $this->assertEquals('read', $permissions[1]->name);
        $this->assertEquals('update', $permissions[2]->name);
        $this->assertEquals('delete', $permissions[3]->name);
    }
}
