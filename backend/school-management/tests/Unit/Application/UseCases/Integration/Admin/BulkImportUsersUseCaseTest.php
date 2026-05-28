<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\General\ImportResponse;
use App\Core\Application\UseCases\Admin\UserManagement\BulkImportUsersUseCase;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Jobs\ClearStaffCacheJob;
use App\Models\Career;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class BulkImportUsersUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $roles = [
            UserRoles::ADMIN->value,
            UserRoles::SUPERVISOR->value,
            UserRoles::STUDENT->value,
            UserRoles::FINANCIAL_STAFF->value,
            UserRoles::UNVERIFIED->value,
        ];

        foreach ($roles as $roleName) {
            SpatieRole::create(['name' => $roleName, 'guard_name' => 'sanctum']);
        }
    }

    #[Test]
    public function it_imports_basic_users_successfully(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $rows = [
            [
                'Juan',               // 0: name
                'Pérez',              // 1: last_name
                'juan@example.com',   // 2: email
                '5551234567',         // 3: phone_number (optional)
                '1990-01-01',         // 4: birthdate (optional)
                'hombre',             // 5: gender (optional)
                'CURP1234567890123',  // 6: curp (required)
                'Calle 123',          // 7: street (optional)
                'Ciudad',             // 8: city (optional)
                'Estado',             // 9: state (optional)
                '12345',              // 10: zip_code (optional)
                'A+',                 // 11: blood_type (optional) ← CORREGIDO
                '2024-01-01',         // 12: registration_date (optional)
                'activo',             // 13: status (optional)
                null,                 // 14: career_id (student detail)
                null,                 // 15: n_control (student detail)
                null,                 // 16: semestre (student detail)
                null,                 // 17: group (student detail)
                null,                 // 18: workshop (student detail)
            ]
        ];


        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertInstanceOf(ImportResponse::class, $result);
        $this->assertEquals(1, $result->getTotalRows());
        $this->assertEquals(1, $result->getInserted());
        $this->assertEquals(0, $result->getErrorsCount());

        // Verificar que se creó el usuario en BD
        $user = User::where('curp', 'CURP1234567890123')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Juan', $user->name);
        $this->assertEquals('Pérez', $user->last_name);
        $this->assertEquals('juan@example.com', $user->email);
        $this->assertEquals('+525551234567', $user->phone_number);
        $this->assertEquals('1990-01-01', $user->birthdate->toDateString());
        $this->assertEquals(UserGender::HOMBRE, $user->gender);
        $this->assertEquals(UserStatus::ACTIVO, $user->status);
        $this->assertEquals(UserBloodType::A_POSITIVE, $user->blood_type);
        $this->assertEquals('2024-01-01', $user->registration_date->toDateString());

        // Verificar que NO tiene student detail
        $this->assertNull($user->studentDetail);

        // Verificar que tiene rol UNVERIFIED
        if (method_exists($user, 'hasRole')) {
            $this->assertTrue($user->hasRole(UserRoles::UNVERIFIED->value));
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
        Mail::assertNothingSent(); // El mail se envía por job
    }

    #[Test]
    public function it_imports_users_with_student_details_successfully(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $career = Career::factory()->create();

        $rows = [
            [
                'María',              // 0: name
                'González',           // 1: last_name
                'maria@example.com',  // 2: email
                '5559876543',         // 3: phone_number
                '1995-05-15',         // 4: birthdate
                'mujer',              // 5: gender
                'CURP9876543210987',  // 6: curp
                'Avenida 456',        // 7: street
                'Ciudad Centro',      // 8: city
                'Estado Centro',      // 9: state
                '54321',              // 10: zip_code
                'O+',                 // 11: blood_type ← CORREGIDO
                '2024-01-15',         // 12: registration_date
                'activo',             // 13: status
                $career->id,          // 14: career_id (¡SÍ tiene student detail!)
                '19201134',           // 15: n_control
                '5',                  // 16: semestre
                'A',                  // 17: group
                'Taller Programación', // 18: workshop
            ]
        ];


        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getInserted());

        $user = User::where('curp', 'CURP9876543210987')->first();
        $this->assertNotNull($user);

        // Verificar que tiene student detail
        $this->assertNotNull($user->studentDetail);
        $this->assertEquals('19201134', $user->studentDetail->n_control);
        $this->assertEquals(5, $user->studentDetail->semestre);
        $this->assertEquals('A', $user->studentDetail->group);
        $this->assertEquals('Taller Programación', $user->studentDetail->workshop);
        $this->assertEquals($career->id, $user->studentDetail->career_id);

        // Verificar que tiene rol STUDENT (por tener student detail)
        if (method_exists($user, 'hasRole')) {
            $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_validates_required_fields_and_adds_errors(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $rows = [
            // Sin nombre
            [
                '',                   // 0: name (faltante)
                'Pérez',
                'juan@example.com',
                '5551234567',
                '1990-01-01',
                'hombre',
                'CURP1234567890123'
            ],
            // Sin apellido
            [
                'Juan',
                '',                   // 1: last_name (faltante)
                'juan2@example.com',
                '5551234568',
                '1990-01-01',
                'hombre',
                'CURP1234567890124'
            ],
            // Sin email
            [
                'Carlos',
                'López',
                '',                   // 2: email (faltante)
                '5551234569',
                '1990-01-01',
                'hombre',
                'CURP1234567890125'
            ],
            // Email inválido
            [
                'Ana',
                'Martínez',
                'email-invalido',     // 2: email (inválido)
                '5551234570',
                '1990-01-01',
                'mujer',
                'CURP1234567890126'
            ],
            // Sin CURP
            [
                'Pedro',
                'Ramírez',
                'pedro@example.com',
                '5551234571',
                '1990-01-01',
                'hombre',
                ''                    // 6: curp (faltante)
            ]
        ];

        // Rellenar con valores nulos para completar el array
        foreach ($rows as &$row) {
            while (count($row) < 21) {
                $row[] = null;
            }
        }

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(5, $result->getTotalRows());
        $this->assertEquals(0, $result->getInserted());
        $this->assertEquals(5, $result->getErrorsCount());

        // No debería haber usuarios creados
        $this->assertEquals(0, User::count());

        Queue::assertPushed(ClearStaffCacheJob::class); // Se dispara aunque no haya inserciones
        Mail::assertNothingSent();
    }

    #[Test]
    public function it_handles_duplicate_users(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        // Crear usuario existente
        User::factory()->create([
            'email' => 'existing@example.com',
            'curp' => 'EXISTINGCURP123'
        ]);

        $rows = [
            [
                'Juan',
                'Pérez',
                'existing@example.com', // Email duplicado
                '5551234567',
                '1990-01-01',
                'hombre',
                'EXISTINGCURP123',      // CURP duplicada
                'Calle 123'
            ]
        ];

        // Rellenar array
        while (count($rows[0]) < 21) {
            $rows[0][] = null;
        }

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getTotalRows());
        $this->assertEquals(0, $result->getInserted());
        $this->assertGreaterThan(0, $result->getErrorsCount());

        // Solo debe existir el usuario original
        $this->assertEquals(1, User::count());

        Queue::assertPushed(ClearStaffCacheJob::class);
        Mail::assertNothingSent();
    }

    #[Test]
    public function it_imports_multiple_users_in_bulk(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $career = Career::factory()->create();
        $totalUsers = 10;

        $rows = [];
        for ($i = 1; $i <= $totalUsers; $i++) {
            $rows[] = [
                "User{$i}",
                "Last{$i}",
                "user{$i}@example.com",
                "+52" . str_pad($i, 10, '0', STR_PAD_LEFT),
                "199" . ($i % 10) . "-01-01",
                $i % 2 == 0 ? 'hombre' : 'mujer',
                "CURP" . str_pad($i, 14, '0', STR_PAD_LEFT),
                "Street {$i}",
                "City {$i}",
                "State {$i}",
                "1234{$i}",
                $i % 3 == 0 ? 'A+' : 'B+',      // blood_type
                '2024-01-01',
                'activo',
                $career->id,                    // Todos tienen student detail
                "1920113{$i}",
                ($i % 10) + 1,
                chr(64 + ($i % 4) + 1),         // A, B, C, D
                "Taller {$i}",
                null,                           // (unused)
                null                            // (unused)
            ];
        }

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals($totalUsers, $result->getTotalRows());
        $this->assertEquals($totalUsers, $result->getInserted());
        $this->assertEquals(0, $result->getErrorsCount());

        // Verificar que todos se crearon
        $this->assertEquals($totalUsers, User::count());
        $this->assertEquals($totalUsers, StudentDetail::count());

        // Verificar datos de un usuario específico
        $user = User::where('email', 'user5@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('User5', $user->name);
        $this->assertEquals('Last5', $user->last_name);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_processes_large_imports_with_chunking(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $totalRows = 250; // Más que CHUNK_SIZE (200)
        $rows = [];
        for ($i = 1; $i <= $totalRows; $i++) {
            $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
            $rows[] = [
                "User{$i}",
                "Last{$i}",
                "user{$i}@example.com",
                "+52" . str_pad($i, 10, '0', STR_PAD_LEFT), // Mejor formato de teléfono
                "2000-{$month}-01",
                $i % 2 == 0 ? 'hombre' : 'mujer', // Agregar género válido
                "CURP" . str_pad($i, 14, '0', STR_PAD_LEFT),  // ← CORREGIDO
                null,
                null,
                null,
                null,
                null,     // blood_type
                null,     // registration_date
                null,     // status
                null,     // career_id
                null,     // n_control
                null,     // semestre
                null,     // group
                null,     // workshop
            ];
        }


        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals($totalRows, $result->getTotalRows());
        $this->assertEquals($totalRows, $result->getInserted());

        $this->assertEquals($totalRows, User::count());

        // Todos deberían tener rol UNVERIFIED (sin student detail)
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $unverifiedUsers = User::role(UserRoles::UNVERIFIED->value)->count();
            $this->assertEquals($totalRows, $unverifiedUsers);
        }
        $user = User::where('email', 'user123@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(18, strlen($user->curp));

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_mixed_users_with_and_without_student_details(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $career = Career::factory()->create();

        $rows = [
            // Sin student detail
            [
                'Juan',
                'Pérez',
                'juan@example.com',
                '5551234567',
                '1990-01-01',
                'hombre',
                'CURP1234567890123'
            ],
            // Con student detail
            [
                'María',
                'González',
                'maria@example.com',
                '5559876543',
                '1995-05-15',
                'mujer',
                'CURP9876543210987',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                $career->id,
                '19201134',
                '5',
                'A',
                'Taller'
            ]
        ];

        // Rellenar arrays
        foreach ($rows as &$row) {
            while (count($row) < 21) {
                $row[] = null;
            }
        }

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(2, $result->getInserted());
        $this->assertEquals(2, User::count());
        $this->assertEquals(1, StudentDetail::count()); // Solo uno tiene student detail

        $juan = User::where('email', 'juan@example.com')->first();
        $maria = User::where('email', 'maria@example.com')->first();

        // Verificar roles
        if (method_exists($juan, 'hasRole')) {
            $this->assertTrue($juan->hasRole(UserRoles::UNVERIFIED->value));
            $this->assertTrue($maria->hasRole(UserRoles::STUDENT->value));
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_generates_temporary_passwords_for_users(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $rows = [
            [
                'Juan',
                'Pérez',
                'juan@example.com',
                '5551234567',
                '1990-01-01',
                'hombre',
                'CURP1234567890123'
            ]
        ];

        // Rellenar array
        while (count($rows[0]) < 21) {
            $rows[0][] = null;
        }

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getInserted());

        $user = User::where('email', 'juan@example.com')->first();
        $this->assertNotNull($user);

        // La contraseña debe estar hasheada
        $this->assertNotEquals('', $user->password);
        $this->assertTrue(strlen($user->password) > 20); // Bcrypt hash es largo

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_optional_fields_correctly(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $rows = [
            [
                'Juan',               // name
                'Pérez',              // last_name
                'juan@example.com',   // email
                "+527357890980",                 // phone_number (opcional)
                "2000-12-1",                 // birthdate (opcional)
                'hombre',                 // gender (opcional)
                'CURP1234567890123',  // curp
                null,
                null,
                null,
                null,
                null,     // blood_type
                null,     // registration_date
                null,     // status
                null,     // career_id
                null,     // n_control
                null,     // semestre
                null,     // group
                null,     // workshop
            ]
        ];

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getInserted());

        $user = User::where('curp', 'CURP1234567890123')->first();
        $this->assertNotNull($user);

        // Campos opcionales deben ser null o valores por defecto
        $this->assertNotEmpty($user->phone_number);
        $this->assertNotEmpty($user->birthdate);
        $this->assertNotEmpty($user->gender);
        $this->assertNull($user->stripe_customer_id);
        $this->assertNull($user->blood_type);

        // Valores por defecto
        $this->assertEquals(UserStatus::ACTIVO, $user->status);
        $this->assertNotNull($user->registration_date);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_database_transaction_rollback_on_error(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $career = Career::factory()->create();

        // Fila 1: Válida
        // Fila 2: Inválida (sin email)
        // Fila 3: Válida (no debería procesarse si hay transacción)
        $rows = [
            [
                'Juan',
                'Pérez',
                'juan@example.com',
                '5551234567',
                '1990-01-01',
                'hombre',
                'CURP1234567890123',
                null, null, null, null, null, null, null, null, null,
                $career->id,
                '19201134',
                '5',
                'A',
                null
            ],
            [
                'María',
                'González',
                '', // Email faltante - causará error de validación
                '5559876543',
                '1995-05-15',
                'mujer',
                'CURP9876543210987',
                null, null, null, null, null, null, null, null, null,
                $career->id,
                '19201135',
                '5',
                'B',
                null
            ],
            [
                'Carlos',
                'López',
                'carlos@example.com',
                '5551234569',
                '1992-08-20',
                'hombre',
                'CURP9876543210988',
                null, null, null, null, null, null, null, null, null,
                $career->id,
                '19201136',
                '3',
                'C',
                null
            ]
        ];

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        // Depende de cómo maneje tu caso de uso los errores en chunks
        // Si usa transacción por chunk, la fila 3 debería procesarse
        // Si falla todo el chunk, solo la fila 1 podría procesarse
        $this->assertGreaterThan(0, $result->getErrorsCount());

        // Verificar que al menos un usuario se creó (la fila válida)
        $this->assertGreaterThan(0, User::count());

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_processes_student_details_only_when_all_required_fields_present(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $career = Career::factory()->create();

        $rows = [
            // Falta n_control - NO debería crear student detail
            [
                'Juan',
                'Pérez',
                'juan@example.com',
                '5551234567',
                '1990-01-01',
                'hombre',
                'CURP1234567890123',
                null, null, null, null, null, null, null,
                $career->id,  // career_id presente
                null,         // n_control faltante
                '5',          // semestre presente
                'A',
                null
            ],
            // Todos los campos presentes - SÍ debería crear student detail
            [
                'María',
                'González',
                'maria@example.com',
                '5559876543',
                '1995-05-15',
                'mujer',
                'CURP9876543210987',
                null, null, null, null, null, null, null,
                $career->id,
                '19201134',   // n_control presente
                '5',
                'B',
                null
            ]
        ];

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(2, $result->getInserted());
        $this->assertEquals(2, User::count());
        $this->assertEquals(1, StudentDetail::count()); // Solo uno tiene todos los campos

        $maria = User::where('email', 'maria@example.com')->first();
        $this->assertNotNull($maria->studentDetail);

        $juan = User::where('email', 'juan@example.com')->first();
        $this->assertNull($juan->studentDetail);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_trims_string_fields(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();
        $career = Career::factory()->create();
        $rows = [
            [
                '  Juan  ',           // name con espacios
                '  Pérez  ',          // last_name con espacios
                '  juan@example.com  ', // email con espacios
                '  5551234567  ',     // phone_number con espacios
                '  1990-01-01  ',     // birthdate con espacios
                '  hombre  ',         // gender con espacios
                '  CURP1234567890123  ', // curp con espacios
                '  Calle 123  ',      // street con espacios
                '  Ciudad  ',         // city con espacios
                '  Estado  ',         // state con espacios
                '  12345  ',          // zip_code con espacios
                '  A+  ',     // blood_type con espacios
                '  2024-01-01  ',     // registration_date con espacios
                '  activo  ',         // status con espacios
                "  {$career->id}  ",              // career_id con espacios (se convertirá a int)
                '  19201134  ',       // n_control con espacios
                '  5  ',              // semestre con espacios (se convertirá a int)
                '  A  ',              // group con espacios
                '  Taller  '          // workshop con espacios
            ]
        ];

        $useCase = app(BulkImportUsersUseCase::class);

        // Act

        $result = $useCase->execute($rows);
        // Assert
        $this->assertEquals(1, $result->getInserted());




        $user = User::where('curp', 'CURP1234567890123')->first();
        $this->assertNotNull($user);

        // Verificar que los campos se trimearon
        $this->assertEquals('Juan', $user->name);
        $this->assertEquals('Pérez', $user->last_name);
        $this->assertEquals('juan@example.com', $user->email);
        $this->assertEquals('+525551234567', $user->phone_number);
        $this->assertEquals('CURP1234567890123', $user->curp);

        // Verificar student detail
        if ($user->studentDetail) {
            $this->assertEquals('19201134', $user->studentDetail->n_control);
            $this->assertEquals(5, $user->studentDetail->semestre);
            $this->assertEquals('A', $user->studentDetail->group);
            $this->assertEquals('Taller', $user->studentDetail->workshop);
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_dispatches_cache_clear_job(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $rows = [
            [
                'Juan',
                'Pérez',
                'juan@example.com',
                '5551234567',
                '1990-01-01',
                'hombre',
                'CURP1234567890123'
            ]
        ];

        // Rellenar array
        while (count($rows[0]) < 21) {
            $rows[0][] = null;
        }

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getInserted());

        // Verificar que se disparó el job
        Queue::assertPushed(ClearStaffCacheJob::class);
        Queue::assertPushed(ClearStaffCacheJob::class, function ($job) {
            return $job->queue === 'cache';
        });
    }

    #[Test]
    public function it_handles_empty_rows_array(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        $rows = [];

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertInstanceOf(ImportResponse::class, $result);
        $this->assertEquals(0, $result->getTotalRows());
        $this->assertEquals(0, $result->getInserted());
        $this->assertEquals(0, $result->getErrorsCount());

        Queue::assertPushed(ClearStaffCacheJob::class); // Se dispara igual
        Mail::assertNothingSent();
    }

    #[Test]
    public function it_handles_exception_in_chunk_and_continues_with_next(): void
    {
        // Arrange
        Queue::fake();
        Mail::fake();

        // Crear más de CHUNK_SIZE rows para forzar múltiples chunks
        $totalRows = 250; // Más de 200 (CHUNK_SIZE)
        $rows = [];

        for ($i = 1; $i <= $totalRows; $i++) {
            $rows[] = [
                "User{$i}",
                "Last{$i}",
                "user{$i}@example.com",
                "+527352770097",
                null,
                null,
                "CURP" . str_pad($i, 14, '0', STR_PAD_LEFT)
            ];

            // Rellenar con nulls
            while (count($rows[$i-1]) < 21) {
                $rows[$i-1][] = null;
            }
        }

        // Hacer que algunos emails sean inválidos para generar errores
        // pero no todos, para que el proceso continúe
        $rows[50][2] = 'email-invalido'; // Fila 51 en chunk 1
        $rows[210][2] = 'otro-invalido'; // Fila 211 en chunk 2

        $useCase = app(BulkImportUsersUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals($totalRows, $result->getTotalRows());
        $this->assertLessThan($totalRows, $result->getInserted()); // Algunos fallaron
        $this->assertGreaterThan(0, $result->getErrorsCount()); // Hay errores

        // Pero algunos usuarios deberían haberse creado
        $this->assertGreaterThan(0, User::count());

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

}
