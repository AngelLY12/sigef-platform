<?php

namespace Tests\Unit\Infraestructure\Mappers;

use App\Models\User as EloquentUser;
use App\Core\Domain\Entities\User as DomainUser;
use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Core\Infraestructure\Mappers\StudentDetailMapper;
use App\Core\Infraestructure\Mappers\RolesAndPermissionMapper;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Entities\Role as DomainRole;
use App\Core\Domain\Entities\StudentDetail as DomainStudentDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class UserMapperTest extends TestCase
{
    #[Test]
    public function it_maps_all_basic_fields_from_eloquent_to_domain(): void
    {
        // Arrange
        $eloquentUser = new EloquentUser();

        // Establece propiedades básicas
        $eloquentUser->id = 1;
        $eloquentUser->curp = 'TEST123456HMNLML09';
        $eloquentUser->name = 'Juan';
        $eloquentUser->last_name = 'Pérez';
        $eloquentUser->email = 'juan.perez@example.com';
        $eloquentUser->password = 'contrasena';
        $eloquentUser->phone_number = '5512345678';
        $eloquentUser->status = UserStatus::ACTIVO;
        $eloquentUser->registration_date = Carbon::parse('2024-01-15');
        $eloquentUser->email_verified_at = Carbon::now();
        $eloquentUser->birthdate = Carbon::parse('2000-05-15');
        $eloquentUser->gender = UserGender::HOMBRE;
        $eloquentUser->address = ['street' => 'Main St', 'city' => 'Mexico City'];
        $eloquentUser->blood_type = UserBloodType::O_POSITIVE;
        $eloquentUser->stripe_customer_id = 'cus_123456';

        // Act
        $domainUser = UserMapper::toDomain($eloquentUser);

        // Assert
        $this->assertInstanceOf(DomainUser::class, $domainUser);

        $this->assertEquals(1, $domainUser->id);
        $this->assertEquals('TEST123456HMNLML09', $domainUser->curp);
        $this->assertEquals('Juan', $domainUser->name);
        $this->assertEquals('Pérez', $domainUser->last_name);
        $this->assertEquals('juan.perez@example.com', $domainUser->email);
        $this->assertEquals('5512345678', $domainUser->phone_number);
        $this->assertSame(UserStatus::ACTIVO, $domainUser->status);
        $this->assertSame(UserGender::HOMBRE, $domainUser->gender);
        $this->assertSame(UserBloodType::O_POSITIVE, $domainUser->blood_type);
        $this->assertEquals(['street' => 'Main St', 'city' => 'Mexico City'], $domainUser->address);
        $this->assertEquals('cus_123456', $domainUser->stripe_customer_id);
        $this->assertTrue($domainUser->emailVerified);
    }

    #[Test]
    public function it_handles_null_email_verification(): void
    {
        $eloquentUser = new EloquentUser();
        $eloquentUser->id = 2;
        $eloquentUser->curp = 'TEST123456HMNLML09';
        $eloquentUser->name = 'Test';
        $eloquentUser->last_name = 'User';
        $eloquentUser->email = 'test@example.com';
        $eloquentUser->password = 'hashed';
        $eloquentUser->phone_number = '5512345678';
        $eloquentUser->status = UserStatus::ACTIVO;
        $eloquentUser->registration_date = Carbon::now();
        $eloquentUser->email_verified_at = null; // Email NO verificado
        $eloquentUser->birthdate = null;
        $eloquentUser->gender = null;
        $eloquentUser->address = null;
        $eloquentUser->blood_type = null;
        $eloquentUser->stripe_customer_id = null;

        $domainUser = UserMapper::toDomain($eloquentUser);

        $this->assertFalse($domainUser->emailVerified);
        $this->assertNull($domainUser->birthdate);
        $this->assertNull($domainUser->gender);
        $this->assertNull($domainUser->address);
        $this->assertNull($domainUser->blood_type);
        $this->assertNull($domainUser->stripe_customer_id);
    }

    #[Test]
    public function it_maps_from_dto_to_persistence_array_correctly(): void
    {
        // Mock Hash::make
        Hash::shouldReceive('make')
            ->once()
            ->with('plainPassword')
            ->andReturn('hashed_password');

        // Arrange - DTO simple con los campos que SÍ tiene
        $dto = new CreateUserDTO(
            name: 'Test',
            last_name: 'User',
            email: 'test@example.com',
            password: 'plainPassword',
            phone_number: '5512345678',
            curp: 'TEST123456HMNLML09',
            birthdate: Carbon::parse('2000-01-01'),
            gender: UserGender::HOMBRE,
            address: ['city' => 'CDMX'],
            blood_type: UserBloodType::A_POSITIVE,
            registration_date: Carbon::parse('2024-01-01'),
            status: UserStatus::ACTIVO
        );

        // Act
        $persistenceArray = UserMapper::toPersistence($dto);

        // Assert
        $this->assertIsArray($persistenceArray);

        // Campos básicos
        $this->assertEquals('Test', $persistenceArray['name']);
        $this->assertEquals('User', $persistenceArray['last_name']);
        $this->assertEquals('test@example.com', $persistenceArray['email']);
        $this->assertEquals('hashed_password', $persistenceArray['password']); // ¡Hash aplicado!
        $this->assertEquals('5512345678', $persistenceArray['phone_number']);
        $this->assertEquals('TEST123456HMNLML09', $persistenceArray['curp']);

        // Enums como objetos (no strings)
        $this->assertSame(UserGender::HOMBRE, $persistenceArray['gender']);
        $this->assertSame(UserBloodType::A_POSITIVE, $persistenceArray['blood_type']);
        $this->assertSame(UserStatus::ACTIVO, $persistenceArray['status']);

        // Campos que SIEMPRE deben estar
        $this->assertArrayHasKey('address', $persistenceArray);
        $this->assertEquals(['city' => 'CDMX'], $persistenceArray['address']);

        // Fechas como Carbon
        $this->assertInstanceOf(Carbon::class, $persistenceArray['birthdate']);
        $this->assertEquals('2000-01-01', $persistenceArray['birthdate']->format('Y-m-d'));

        $this->assertInstanceOf(Carbon::class, $persistenceArray['registration_date']);
        $this->assertEquals('2024-01-01', $persistenceArray['registration_date']->format('Y-m-d'));

        // stripe_customer_id debe estar en el array PERO como null (viene del DTO que no lo tiene)
        $this->assertArrayHasKey('stripe_customer_id', $persistenceArray);
        $this->assertNull($persistenceArray['stripe_customer_id']);

        // Campos que NO deben estar
        $this->assertArrayNotHasKey('id', $persistenceArray);
        $this->assertArrayNotHasKey('emailVerified', $persistenceArray);
    }

    #[Test]
    public function it_hashes_password_correctly_in_persistence(): void
    {
        Hash::shouldReceive('make')
            ->once()
            ->with('myPassword123')
            ->andReturn('hashed_result_123');

        $dto = new CreateUserDTO(
            name: 'Test',
            last_name: 'User',
            email: 'test@example.com',
            password: 'myPassword123', // Password plano
            phone_number: '5512345678',
            curp: 'TEST123456HMNLML09',
            birthdate: null,
            gender: null,
            address: null,
            blood_type: null,
            registration_date: null,
            status: UserStatus::ACTIVO
        );

        $result = UserMapper::toPersistence($dto);

        // ¡Hash::make debe ser llamado con el password plano!
        $this->assertEquals('hashed_result_123', $result['password']);
    }

    #[Test]
    public function it_sets_registration_date_to_now_when_null(): void
    {
        Hash::shouldReceive('make')->andReturn('hashed');

        $dto = new CreateUserDTO(
            name: 'Test',
            last_name: 'User',
            email: 'test@example.com',
            password: 'pass',
            phone_number: '5512345678',
            curp: 'TEST123456HMNLML09',
            birthdate: null,
            gender: null,
            address: null,
            blood_type: null,
            registration_date: null, // ¡NULL!
            status: UserStatus::ACTIVO
        );

        $result = UserMapper::toPersistence($dto);

        // Debe ser Carbon::now()
        $this->assertInstanceOf(Carbon::class, $result['registration_date']);
        $this->assertTrue($result['registration_date']->diffInSeconds(Carbon::now()) < 2);
    }

    #[Test]
    public function it_preserves_registration_date_when_provided(): void
    {
        Hash::shouldReceive('make')->andReturn('hashed');

        $specificDate = Carbon::parse('2023-12-25 10:30:00');

        $dto = new CreateUserDTO(
            name: 'Test',
            last_name: 'User',
            email: 'test@example.com',
            password: 'pass',
            phone_number: '5512345678',
            curp: 'TEST123456HMNLML09',
            birthdate: null,
            gender: null,
            address: null,
            blood_type: null,
            registration_date: $specificDate, // Fecha específica
            status: UserStatus::ACTIVO
        );

        $result = UserMapper::toPersistence($dto);

        $this->assertSame($specificDate, $result['registration_date']);
        $this->assertEquals('2023-12-25 10:30:00', $result['registration_date']->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_handles_all_enum_values_correctly(): void
    {
        // Test rápido de que todos los enums funcionan
        Hash::shouldReceive('make')->andReturn('hashed');

        $testCases = [
            ['gender' => UserGender::HOMBRE, 'blood' => UserBloodType::A_POSITIVE, 'status' => UserStatus::ACTIVO],
            ['gender' => UserGender::MUJER, 'blood' => UserBloodType::B_NEGATIVE, 'status' => UserStatus::BAJA_TEMPORAL],
            ['gender' => null, 'blood' => null, 'status' => UserStatus::BAJA],
            ['gender' => null, 'blood' => UserBloodType::O_POSITIVE, 'status' => UserStatus::ELIMINADO],
        ];

        foreach ($testCases as $case) {
            $dto = new CreateUserDTO(
                name: 'Test',
                last_name: 'User',
                email: 'test@example.com',
                password: 'pass',
                phone_number: '5512345678',
                curp: 'TEST123456HMNLML09',
                birthdate: null,
                gender: $case['gender'],
                address: null,
                blood_type: $case['blood'],
                registration_date: null,
                status: $case['status']
            );

            $result = UserMapper::toPersistence($dto);

            $this->assertSame($case['gender'], $result['gender']);
            $this->assertSame($case['blood'], $result['blood_type']);
            $this->assertSame($case['status'], $result['status']);
        }
    }

    #[Test]
    public function it_maps_relations_when_loaded(): void
    {
        $eloquentUser = new EloquentUser();
        $eloquentUser->id = 1;
        $eloquentUser->curp = 'TEST123456HMNLML09';
        $eloquentUser->name = 'Test';
        $eloquentUser->last_name = 'User';
        $eloquentUser->email = 'test@example.com';
        $eloquentUser->password = 'hashed';
        $eloquentUser->phone_number = '5512345678';
        $eloquentUser->status = UserStatus::ACTIVO;
        $eloquentUser->registration_date = Carbon::now();

        // Mock student detail relation
        $studentDetail = new \App\Models\StudentDetail();
        $studentDetail->id = 10;
        $studentDetail->user_id = 1;
        $eloquentUser->setRelation('studentDetail', $studentDetail);

        // Mock roles relation
        $role = new \Spatie\Permission\Models\Role();
        $role->id = 1;
        $role->name = 'admin';
        $eloquentUser->setRelation('roles', new \Illuminate\Database\Eloquent\Collection([$role]));

        $domainUser = UserMapper::toDomain($eloquentUser);

        // Verifica relaciones mapeadas
        $this->assertNotNull($domainUser->studentDetail);
        $this->assertEquals(10, $domainUser->studentDetail->id);

        $this->assertCount(1, $domainUser->roles);
        $this->assertEquals('admin', $domainUser->roles[0]->name);
    }

    #[Test]
    public function it_does_not_map_relations_when_not_loaded(): void
    {
        $eloquentUser = new EloquentUser();
        $eloquentUser->id = 1;
        $eloquentUser->curp = 'TEST123456HMNLML09';
        $eloquentUser->name = 'Test';
        $eloquentUser->last_name = 'User';
        $eloquentUser->email = 'test@example.com';
        $eloquentUser->password = 'hashed';
        $eloquentUser->phone_number = '5512345678';
        $eloquentUser->status = UserStatus::ACTIVO;
        $eloquentUser->registration_date = Carbon::now();

        // NO establecer relaciones

        $domainUser = UserMapper::toDomain($eloquentUser);

        $this->assertNull($domainUser->studentDetail);
        $this->assertEquals([], $domainUser->roles);
    }

}
