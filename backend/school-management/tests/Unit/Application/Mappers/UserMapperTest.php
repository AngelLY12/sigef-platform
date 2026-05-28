<?php

namespace Tests\Unit\Application\Mappers;

use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\DTO\Request\User\UpdateUserPermissionsDTO;
use App\Core\Application\DTO\Request\User\UpdateUserRoleDTO;
use App\Core\Application\DTO\Response\User\PromotedStudentsResponse;
use App\Core\Application\DTO\Response\User\UserAuthResponse;
use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Application\DTO\Response\User\UserDataResponse;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Application\DTO\Response\User\UserRecipientDTO;
use App\Core\Application\DTO\Response\User\UserWithUpdatedPermissionsResponse;
use App\Core\Application\DTO\Response\User\UserWithUpdatedRoleResponse;
use App\Core\Application\Mappers\UserMapper;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserStatus;
use App\Models\User;
use App\Core\Domain\Entities\User as DomainUser;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserMapperTest extends TestCase
{
    #[Test]
    public function it_maps_create_user_dto_to_domain_user(): void
    {
        // Arrange
        $userDTO = new CreateUserDTO(
            name: 'Juan',
            last_name: 'Pérez',
            email: 'juan@example.com',
            password: 'password123',
            phone_number: '5551234567',
            curp: 'CURP1234567890123',
            birthdate: Carbon::createFromDate(1990, 1, 1),
            gender: UserGender::HOMBRE,
            address: ['Calle 123', 'Ciudad'],
            blood_type: UserBloodType::A_POSITIVE,
            registration_date: Carbon::now(),
            status: UserStatus::ACTIVO
        );

        // Act
        $result = UserMapper::toDomain($userDTO);

        // Assert
        $this->assertInstanceOf(DomainUser::class, $result);
        $this->assertEquals('CURP1234567890123', $result->curp);
        $this->assertEquals('Juan', $result->name);
        $this->assertEquals('Pérez', $result->last_name);
        $this->assertEquals('juan@example.com', $result->email);
        $this->assertEquals('password123', $result->password);
        $this->assertEquals('5551234567', $result->phone_number);
        $this->assertEquals(UserStatus::ACTIVO, $result->status);
        $this->assertInstanceOf(Carbon::class, $result->registration_date);
        $this->assertInstanceOf(Carbon::class, $result->birthdate);
        $this->assertEquals(UserGender::HOMBRE, $result->gender);
        $this->assertEquals(['Calle 123', 'Ciudad'], $result->address);
        $this->assertEquals(UserBloodType::A_POSITIVE, $result->blood_type);
        $this->assertNull($result->stripe_customer_id);
    }

    #[Test]
    public function it_maps_user_to_user_auth_response(): void
    {
        // Arrange
        $user = User::factory()->make([
            'id' => 1,
            'curp' => 'CURP1234567890123',
            'name' => 'María',
            'last_name' => 'González',
            'email' => 'maria@example.com',
            'phone_number' => '5559876543',
            'status' => UserStatus::ACTIVO,
            'registration_date' => Carbon::createFromDate(2023, 1, 1),
            'email_verified_at' => Carbon::createFromDate(2023, 1, 2),
            'birthdate' => Carbon::createFromDate(1995, 5, 15),
            'gender' => UserGender::MUJER,
            'address' => ['Avenida 456', 'Colonia Centro'],
            'blood_type' => UserBloodType::O_POSITIVE,
            'stripe_customer_id' => 'cus_123456'
        ]);

        // Act
        $result = UserMapper::toUserAuthResponse($user);

        // Assert
        $this->assertInstanceOf(UserAuthResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('CURP1234567890123', $result->curp);
        $this->assertEquals('María', $result->name);
        $this->assertEquals('González', $result->last_name);
        $this->assertEquals('maria@example.com', $result->email);
        $this->assertEquals('5559876543', $result->phone_number);
        $this->assertEquals('activo', $result->status);
        $this->assertEquals('2023-01-01', $result->registration_date);
        $this->assertEquals('2023-01-02', $result->emailVerifiedAt);
        $this->assertEquals('1995-05-15', $result->birthdate);
        $this->assertEquals('mujer', $result->gender);
        $this->assertEquals(['Avenida 456', 'Colonia Centro'], $result->address);
        $this->assertEquals('O+', $result->blood_type);
        $this->assertEquals('cus_123456', $result->stripe_customer_id);
    }

    #[Test]
    public function it_maps_array_to_create_user_dto(): void
    {
        // Arrange
        $data = [
            'name' => 'Carlos',
            'last_name' => 'López',
            'email' => 'carlos@example.com',
            'password' => 'secure123',
            'phone_number' => '5555555555',
            'curp' => 'CURP9876543210987',
            'birthdate' => '1992-08-20',
            'gender' => 'hombre',
            'address' => ['Calle Principal 789'],
            'blood_type' => 'B+',
            'registration_date' => '2024-01-15',
            'status' => 'activo'
        ];

        // Act
        $result = UserMapper::toCreateUserDTO($data);

        // Assert
        $this->assertInstanceOf(CreateUserDTO::class, $result);
        $this->assertEquals('Carlos', $result->name);
        $this->assertEquals('López', $result->last_name);
        $this->assertEquals('carlos@example.com', $result->email);
        $this->assertEquals('secure123', $result->password);
        $this->assertEquals('5555555555', $result->phone_number);
        $this->assertEquals('CURP9876543210987', $result->curp);
        $this->assertEquals('1992-08-20', $result->birthdate->toDateString());
        $this->assertEquals(UserGender::HOMBRE, $result->gender);
        $this->assertEquals(['Calle Principal 789'], $result->address);
        $this->assertEquals(UserBloodType::B_POSITIVE, $result->blood_type);
        $this->assertEquals('2024-01-15', $result->registration_date->toDateString());
        $this->assertEquals(UserStatus::ACTIVO, $result->status);
    }

    #[Test]
    public function it_maps_domain_user_to_data_response(): void
    {
        // Arrange
        $domainUser = new DomainUser(
            curp: 'CURP1234567890123',
            name: 'Ana',
            last_name: 'Martínez',
            email: 'ana@example.com',
            password: 'password',
            phone_number: '5551112233',
            status: UserStatus::ACTIVO,
            registration_date: Carbon::now(),
            id: 1,
            birthdate: Carbon::createFromDate(1993, 3, 10),
            gender: UserGender::MUJER,
            address: [],
            blood_type: UserBloodType::AB_POSITIVE
        );

        // Act
        $result = UserMapper::toDataResponse($domainUser);

        // Assert
        $this->assertInstanceOf(UserDataResponse::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Ana Martínez', $result->fullName);
        $this->assertEquals('ana@example.com', $result->email);
        $this->assertEquals('CURP1234567890123', $result->curp);
        $this->assertNull($result->n_control);
    }

    #[Test]
    public function it_maps_array_to_recipient_dto(): void
    {
        // Arrange
        $userData = [
            'id' => 100,
            'name' => 'Pedro',
            'last_name' => 'Ramírez',
            'email' => 'pedro@example.com'
        ];

        // Act
        $result = UserMapper::toRecipientDTO($userData);

        // Assert
        $this->assertInstanceOf(UserRecipientDTO::class, $result);
        $this->assertEquals(100, $result->id);
        $this->assertEquals('Pedro Ramírez', $result->fullName);
        $this->assertEquals('pedro@example.com', $result->email);
    }

    #[Test]
    public function it_maps_array_to_user_id_list_dto(): void
    {
        // Arrange
        $ids = [1, 2, 3, 4, 5];

        // Act
        $result = UserMapper::toUserIdListDTO($ids);

        // Assert
        $this->assertInstanceOf(UserIdListDTO::class, $result);
        $this->assertEquals([1, 2, 3, 4, 5], $result->userIds);
    }

    #[Test]
    public function it_maps_array_to_update_user_permissions_dto(): void
    {
        // Arrange
        $data = [
            'curps' => ['CURP1', 'CURP2', 'CURP3'],
            'role' => 'admin',
            'permissionsToAdd' => ['create', 'update'],
            'permissionsToRemove' => ['delete']
        ];

        // Act
        $result = UserMapper::toUpdateUserPermissionsDTO($data);

        // Assert
        $this->assertInstanceOf(UpdateUserPermissionsDTO::class, $result);
        $this->assertEquals(['CURP1', 'CURP2', 'CURP3'], $result->curps);
        $this->assertEquals('admin', $result->role);
        $this->assertEquals(['create', 'update'], $result->permissionsToAdd);
        $this->assertEquals(['delete'], $result->permissionsToRemove);
    }

    #[Test]
    public function it_maps_data_to_user_with_updated_permissions_response(): void
    {
        // Arrange
        $user = User::factory()->make([
            'name' => 'Laura',
            'last_name' => 'Sánchez',
            'curp' => 'CURP5555555555555'
        ]);

        $permissions = ['read', 'write'];
        $role = 'editor';
        $metadata = ['timestamp' => '2024-01-01 12:00:00'];

        // Act
        $result = UserMapper::toUserUpdatedPermissionsResponse(
            $permissions,
            $metadata,
            $user,
            $role,
        );

        // Assert
        $this->assertInstanceOf(UserWithUpdatedPermissionsResponse::class, $result);
        $this->assertEquals('Laura Sánchez', $result->fullName);
        $this->assertEquals('CURP5555555555555', $result->curp);
        $this->assertEquals('editor', $result->role);
        $this->assertEquals(['read', 'write'], $result->updatedPermissions);
        $this->assertEquals(['timestamp' => '2024-01-01 12:00:00'], $result->metadata);
    }

    #[Test]
    public function it_maps_array_to_update_user_role_dto(): void
    {
        // Arrange
        $data = [
            'curps' => ['CURP001', 'CURP002'],
            'rolesToAdd' => ['admin', 'moderator'],
            'rolesToRemove' => ['user']
        ];

        // Act
        $result = UserMapper::toUpdateUserRoleDTO($data);

        // Assert
        $this->assertInstanceOf(UpdateUserRoleDTO::class, $result);
        $this->assertEquals(['CURP001', 'CURP002'], $result->curps);
        $this->assertEquals(['admin', 'moderator'], $result->rolesToAdd);
        $this->assertEquals(['user'], $result->rolesToRemove);
    }

    #[Test]
    public function it_maps_data_to_user_with_updated_role_response(): void
    {
        // Arrange
        $data = [
            'names' => ['Juan Pérez', 'María Gómez'],
            'curps' => ['CURP123', 'CURP456'],
            'roles' => ['admin', 'editor'],
            'metadata' => ['operation' => 'bulk_update']
        ];

        // Act
        $result = UserMapper::toUserWithUptadedRoleResponse($data);

        // Assert
        $this->assertInstanceOf(UserWithUpdatedRoleResponse::class, $result);
        $this->assertEquals(['Juan Pérez', 'María Gómez'], $result->fullNames);
        $this->assertEquals(['CURP123', 'CURP456'], $result->curps);
        $this->assertEquals(['admin', 'editor'], $result->updatedRoles);
        $this->assertEquals(['operation' => 'bulk_update'], $result->metadata);
    }

    #[Test]
    public function it_maps_data_to_user_changed_status_response(): void
    {
        // Arrange
        $data = [
            'status' => 'INACTIVO',
            'total' => 25
        ];

        // Act
        $result = UserMapper::toUserChangedStatusResponse($data);

        // Assert
        $this->assertInstanceOf(UserChangedStatusResponse::class, $result);
        $this->assertEquals('INACTIVO', $result->newStatus);
        $this->assertEquals(25, $result->totalUpdated);
    }

    #[Test]
    public function it_maps_data_to_promoted_students_response(): void
    {
        // Arrange
        $data = [
            'promotedStudents' => 150,
            'desactivatedStudents' => 10
        ];

        // Act
        $result = UserMapper::toPromotedStudentsResponse($data);

        // Assert
        $this->assertInstanceOf(PromotedStudentsResponse::class, $result);
        $this->assertEquals(150, $result->promotedStudents);
        $this->assertEquals(10, $result->desactivatedStudents);
    }

    #[Test]
    public function it_handles_null_values_in_create_user_dto_to_domain_user(): void
    {
        // Arrange
        $userDTO = new CreateUserDTO(
            name: 'Juan',
            last_name: 'Pérez',
            email: 'juan@example.com',
            password: 'password123',
            phone_number: '5551234567',
            curp: 'CURP1234567890123',
            birthdate: null,
            gender: null,
            address: [],
            blood_type: null,
            registration_date: Carbon::now(),
            status: UserStatus::ACTIVO
        );

        // Act
        $result = UserMapper::toDomain($userDTO);

        // Assert
        $this->assertInstanceOf(DomainUser::class, $result);
        $this->assertEquals('CURP1234567890123', $result->curp);
        $this->assertEquals('Juan', $result->name);
        $this->assertEquals('Pérez', $result->last_name);
        $this->assertEquals('juan@example.com', $result->email);
        $this->assertEquals('password123', $result->password);
        $this->assertEquals('5551234567', $result->phone_number);
        $this->assertEquals(UserStatus::ACTIVO,$result->status);
        $this->assertNull($result->birthdate);
        $this->assertNull($result->gender);
        $this->assertEquals([], $result->address);
        $this->assertNull($result->blood_type);
        $this->assertNull($result->stripe_customer_id);
    }

    #[Test]
    public function it_handles_optional_fields_in_array_to_create_user_dto(): void
    {
        // Arrange
        $data = [
            'name' => 'Carlos',
            'last_name' => 'López',
            'email' => 'carlos@example.com',
            'password' => 'secure123',
            'phone_number' => '5555555555',
            'curp' => 'CURP9876543210987'
        ];

        // Act
        $result = UserMapper::toCreateUserDTO($data);

        // Assert
        $this->assertInstanceOf(CreateUserDTO::class, $result);
        $this->assertEquals('Carlos', $result->name);
        $this->assertEquals('López', $result->last_name);
        $this->assertEquals('carlos@example.com', $result->email);
        $this->assertEquals('secure123', $result->password);
        $this->assertEquals('5555555555', $result->phone_number);
        $this->assertEquals('CURP9876543210987', $result->curp);
        $this->assertNull($result->birthdate);
        $this->assertNull($result->gender);
        $this->assertEquals(null, $result->address);
        $this->assertNull($result->blood_type);
        $this->assertInstanceOf(Carbon::class, $result->registration_date);
        $this->assertEquals(UserStatus::ACTIVO, $result->status);
    }

    #[Test]
    public function it_handles_empty_user_in_to_user_updated_permissions_response(): void
    {
        // Arrange
        $permissions = ['read'];
        $metadata = ['timestamp' => '2024-01-01'];

        // Act
        $result = UserMapper::toUserUpdatedPermissionsResponse(
            $permissions,
            $metadata,
            null,
            null,
        );

        // Assert
        $this->assertInstanceOf(UserWithUpdatedPermissionsResponse::class, $result);
        $this->assertNull($result->fullName);
        $this->assertNull($result->curp);
        $this->assertNull($result->role);
        $this->assertEquals(['read'], $result->updatedPermissions);
        $this->assertEquals(['timestamp' => '2024-01-01'], $result->metadata);
    }

    #[Test]
    public function it_handles_empty_array_in_to_user_id_list_dto(): void
    {
        // Arrange
        $ids = [];

        // Act
        $result = UserMapper::toUserIdListDTO($ids);

        // Assert
        $this->assertInstanceOf(UserIdListDTO::class, $result);
        $this->assertEquals([], $result->userIds);
    }

}
