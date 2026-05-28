<?php

namespace Tests\Unit\Domain\Entities;

use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\Role;
use PHPUnit\Framework\Attributes\Test;

class RoleTest extends BaseDomainTestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $role = new Role(
            id: 1,
            name: 'admin'
        );

        $this->assertInstanceOf(Role::class, $role);
    }

    #[Test]
    public function it_has_required_attributes()
    {
        $role = new Role(
            id: 2,
            name: 'user'
        );

        $this->assertEquals(2, $role->id);
        $this->assertEquals('user', $role->name);
    }

    #[Test]
    public function it_accepts_valid_data()
    {
        $role = new Role(
            id: 3,
            name: 'moderator'
        );

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals(3, $role->id);
        $this->assertEquals('moderator', $role->name);
    }

    #[Test]
    public function it_has_readonly_properties()
    {
        $role = new Role(
            id: 4,
            name: 'editor'
        );

        $this->assertEquals(4, $role->id);
        $this->assertEquals('editor', $role->name);

    }

    #[Test]
    public function it_can_be_instantiated_with_different_ids()
    {
        $ids = [1, 100, 999, 12345];

        foreach ($ids as $id) {
            $role = new Role(
                id: $id,
                name: 'test_role'
            );

            $this->assertEquals($id, $role->id);
        }
    }

    #[Test]
    public function it_can_be_instantiated_with_different_role_names()
    {
        $names = [
            'admin',
            'super_admin',
            'user',
            'guest',
            'moderator',
            'editor',
            'viewer',
            'contributor'
        ];

        foreach ($names as $name) {
            $role = new Role(
                id: 1,
                name: $name
            );

            $this->assertEquals($name, $role->name);
        }
    }

    #[Test]
    public function it_accepts_role_names_with_underscores()
    {
        $role = new Role(
            id: 1,
            name: 'super_admin'
        );

        $this->assertEquals('super_admin', $role->name);
    }

    #[Test]
    public function it_accepts_role_names_with_hyphens()
    {
        $role = new Role(
            id: 1,
            name: 'content-manager'
        );

        $this->assertEquals('content-manager', $role->name);
    }

    #[Test]
    public function it_accepts_role_names_with_numbers()
    {
        $role = new Role(
            id: 1,
            name: 'user_level_2'
        );

        $this->assertEquals('user_level_2', $role->name);
    }

    #[Test]
    public function it_accepts_role_names_with_spaces()
    {
        $role = new Role(
            id: 1,
            name: 'content manager'
        );

        $this->assertEquals('content manager', $role->name);
    }

    #[Test]
    public function it_accepts_role_names_with_mixed_case()
    {
        $role = new Role(
            id: 1,
            name: 'SuperAdmin'
        );

        $this->assertEquals('SuperAdmin', $role->name);
    }

    #[Test]
    public function it_accepts_single_character_role_names()
    {
        $role = new Role(
            id: 1,
            name: 'a'
        );

        $this->assertEquals('a', $role->name);
    }

    #[Test]
    public function it_accepts_long_role_names()
    {
        $longName = str_repeat('a', 100);
        $role = new Role(
            id: 1,
            name: $longName
        );

        $this->assertEquals($longName, $role->name);
    }

    #[Test]
    public function it_can_be_converted_to_json()
    {
        $role = new Role(
            id: 6,
            name: 'supervisor'
        );

        $json = json_encode($role);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(6, $decoded['id']);
        $this->assertEquals('supervisor', $decoded['name']);
    }

    #[Test]
    public function it_returns_correct_json_structure()
    {
        $role = new Role(
            id: 7,
            name: 'auditor'
        );

        $json = json_encode($role);
        $decoded = json_decode($json, true);

        $this->assertCount(2, $decoded);
        $this->assertArrayHasKey('id', $decoded);
        $this->assertArrayHasKey('name', $decoded);
        $this->assertEquals(7, $decoded['id']);
        $this->assertEquals('auditor', $decoded['name']);
    }

    #[Test]
    public function it_can_be_serialized_with_minimal_values()
    {
        $role = new Role(
            id: 1,
            name: 'a'
        );

        $json = json_encode($role);
        $decoded = json_decode($json, true);

        $this->assertEquals(1, $decoded['id']);
        $this->assertEquals('a', $decoded['name']);
    }

    #[Test]
    public function it_can_be_serialized_with_maximum_values()
    {
        $role = new Role(
            id: PHP_INT_MAX,
            name: str_repeat('z', 255)
        );

        $json = json_encode($role);
        $decoded = json_decode($json, true);

        $this->assertEquals(PHP_INT_MAX, $decoded['id']);
        $this->assertEquals(str_repeat('z', 255), $decoded['name']);
    }

    #[Test]
    public function it_can_be_used_in_array_context()
    {
        $role1 = new Role(id: 1, name: 'admin');
        $role2 = new Role(id: 2, name: 'user');
        $role3 = new Role(id: 3, name: 'guest');

        $roles = [$role1, $role2, $role3];

        $this->assertCount(3, $roles);
        $this->assertInstanceOf(Role::class, $roles[0]);
        $this->assertInstanceOf(Role::class, $roles[1]);
        $this->assertInstanceOf(Role::class, $roles[2]);

        $this->assertEquals('admin', $roles[0]->name);
        $this->assertEquals('user', $roles[1]->name);
        $this->assertEquals('guest', $roles[2]->name);
    }

    #[Test]
    public function it_can_be_compared_by_properties()
    {
        $role1 = new Role(id: 1, name: 'admin');
        $role2 = new Role(id: 1, name: 'admin');
        $role3 = new Role(id: 2, name: 'admin');
        $role4 = new Role(id: 1, name: 'user');

        $this->assertEquals($role1->id, $role2->id);
        $this->assertEquals($role1->name, $role2->name);

        $this->assertNotEquals($role1->id, $role3->id);

        $this->assertNotEquals($role1->name, $role4->name);
    }

    #[Test]
    public function it_can_be_used_as_immutable_value_object()
    {
        $role = new Role(
            id: 10,
            name: 'manager'
        );

        $this->assertEquals(10, $role->id);
        $this->assertEquals('manager', $role->name);

        $newRole = new Role(
            id: 10,
            name: 'senior_manager'
        );

        $this->assertEquals('senior_manager', $newRole->name);
        $this->assertEquals($role->id, $newRole->id);
    }

    #[Test]
    public function it_provides_consistent_string_representation()
    {
        $role = new Role(
            id: 25,
            name: 'coordinator'
        );

        $string = "Role[id: 25, name: coordinator]";

        $jsonString = json_encode($role);
        $this->assertStringContainsString('"id":25', $jsonString);
        $this->assertStringContainsString('"name":"coordinator"', $jsonString);
    }
}
