<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\General\ImportResponse;
use App\Core\Application\UseCases\Admin\StudentManagement\BulkImportStudentDetailsUseCase;
use App\Jobs\ClearStaffCacheJob;
use App\Models\Career;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BulkImportStudentDetailsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_imports_student_details_successfully(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user = User::factory()->create([
            'curp' => 'CURP1234567890123'
        ]);

        $rows = [
            [
                'CURP1234567890123',
                $career->id,
                '19201134',
                '5',
                'A',
                'Taller de Programación'
            ]
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertInstanceOf(ImportResponse::class, $result);
        $this->assertEquals(1, $result->getTotalRows());
        $this->assertEquals(1, $result->getInserted());
        $this->assertEquals(0, $result->getErrorsCount());

        // Verificar que se insertó en la BD
        $studentDetail = StudentDetail::where('user_id', $user->id)->first();
        $this->assertNotNull($studentDetail);
        $this->assertEquals('19201134', $studentDetail->n_control);
        $this->assertEquals(5, $studentDetail->semestre);
        $this->assertEquals('A', $studentDetail->group);
        $this->assertEquals('Taller de Programación', $studentDetail->workshop);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_imports_multiple_student_details_in_bulk(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $users = User::factory()->count(5)->create();

        $rows = [];
        foreach ($users as $index => $user) {
            $rows[] = [
                 $user->curp,
                 $career->id,
                 '1920113' . $index,
                 $index + 1,
                 chr(65 + $index), // A, B, C, D, E
                 'Taller ' . ($index + 1)
            ];
        }

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(5, $result->getTotalRows());
        $this->assertEquals(5, $result->getInserted());
        $this->assertEquals(0, $result->getErrorsCount());

        // Verificar que todos se insertaron
        $studentDetailsCount = StudentDetail::count();
        $this->assertEquals(5, $studentDetailsCount);

        foreach ($users as $index => $user) {
            $detail = StudentDetail::where('user_id', $user->id)->first();
            $this->assertNotNull($detail);
            $this->assertEquals('1920113' . $index, $detail->n_control);
            $this->assertEquals($index + 1, $detail->semestre);
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_processes_large_imports_with_chunking(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $totalRows = 250; // Más que CHUNK_SIZE (200)
        $users = User::factory()->count($totalRows)->create();

        $rows = [];
        foreach ($users as $index => $user) {
            $rows[] = [
                 $user->curp,
                 $career->id,
                 'CTRL' . str_pad($index, 6, '0', STR_PAD_LEFT),
                ($index % 10) + 1,
                 'A',
                 'Taller'
            ];
        }

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals($totalRows, $result->getTotalRows());
        $this->assertEquals($totalRows, $result->getInserted());
        $this->assertEquals(0, $result->getErrorsCount());

        $studentDetailsCount = StudentDetail::count();
        $this->assertEquals($totalRows, $studentDetailsCount);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_rows_with_missing_optional_fields(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user = User::factory()->create(['curp' => 'CURP1234567890123']);

        $rows = [
            [
                 'CURP1234567890123',
                 $career->id,
                 '19201134',
                 5,
                // group y workshop son opcionales
            ]
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getInserted());

        $detail = StudentDetail::where('user_id', $user->id)->first();
        $this->assertNotNull($detail);
        $this->assertNull($detail->group);
        $this->assertNull($detail->workshop);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_validates_required_fields_and_adds_errors(): void
    {
        // Arrange
        Queue::fake();

        $rows = [
            // Sin CURP
            [
                 1,
                 '19201134',
                 5
            ],
            // Sin career_id
            [
                'CURP1234567890123',
                 '19201135',
                 5
            ],
            // Sin n_control
            [
                 'CURP1234567890124',
                 1,
                 5
            ],
            // Sin semestre
            [
                'CURP1234567890125',
                 1,
                '19201136'
            ],
            // CURP no encontrada
            [
                'NONEXISTENTCURP',
                 1,
                 '19201137',
                 5
            ]
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(5, $result->getTotalRows());
        $this->assertEquals(0, $result->getInserted());
        $this->assertEquals(5, $result->getErrorsCount());

        // No debería haber ningún student detail insertado
        $this->assertEquals(0, StudentDetail::count());

        Queue::assertNothingPushed(); // No se dispara job porque no hubo inserciones
    }

    #[Test]
    public function it_handles_duplicate_student_details(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user = User::factory()->create(['curp' => 'CURP1234567890123']);

        // Crear un student detail existente
        StudentDetail::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'n_control' => '19201134'
        ]);

        // Intentar importar el mismo registro
        $rows = [
            [
                 'CURP1234567890123',
                 $career->id,
                '19201134', // Mismo n_control
                 5,
                 'A'
            ]
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getTotalRows());
        $this->assertEquals(0, $result->getInserted());
        $this->assertGreaterThan(0, $result->getErrorsCount()); // Debería tener error de duplicado

        // Solo debería haber un registro (el original)
        $this->assertEquals(1, StudentDetail::where('user_id', $user->id)->count());

        Queue::assertNothingPushed(); // No hubo nuevas inserciones
    }

    #[Test]
    public function it_processes_mixed_valid_and_invalid_rows(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $validUser = User::factory()->create(['curp' => 'VALIDCURP1234567']);

        $rows = [
            // Válido
            [
                 'VALIDCURP1234567',
                 $career->id,
                 '19201134',
                 5,
                 'A'
            ],
            // Inválido - sin CURP
            [
                 $career->id,
                '19201135',
                 5
            ],
            // Inválido - CURP no existe
            [
                'INVALIDCURP123456',
                 $career->id,
                '19201136',
                 5
            ],
            // Válido (si existiera el usuario)
            // Este testará que se procesan por chunks
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(3, $result->getTotalRows());
        $this->assertEquals(1, $result->getInserted());
        $this->assertEquals(2, $result->getErrorsCount());

        // Solo el válido debería estar insertado
        $this->assertEquals(1, StudentDetail::count());
        $detail = StudentDetail::where('user_id', $validUser->id)->first();
        $this->assertNotNull($detail);
        $this->assertEquals('19201134', $detail->n_control);

        Queue::assertPushed(ClearStaffCacheJob::class); // Hubo al menos una inserción
    }

    #[Test]
    public function it_handles_empty_rows_array(): void
    {
        // Arrange
        Queue::fake();

        $rows = [];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertInstanceOf(ImportResponse::class, $result);
        $this->assertEquals(0, $result->getTotalRows());
        $this->assertEquals(0, $result->getInserted());
        $this->assertEquals(0, $result->getErrorsCount());

        Queue::assertNothingPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_duplicate_curps_in_same_import(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user = User::factory()->create(['curp' => 'CURP1234567890123']);

        // Misma CURP, diferentes n_control
        $rows = [
            [
                'CURP1234567890123',
                 $career->id,
                '19201134',
                 5
            ],
            [
                'CURP1234567890123', // Misma CURP
                 $career->id,
                '19201135', // Diferente n_control
                6
            ]
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        // Ambos deberían insertarse si el usuario solo tenía un CURP
        $this->assertEquals(2, $result->getTotalRows());
        $this->assertEquals(2, $result->getInserted()); // O 1 si hay validación de duplicado por usuario

        // Verificar cuántos se insertaron realmente
        $detailsCount = StudentDetail::where('user_id', $user->id)->count();
        $this->assertLessThanOrEqual(2, $detailsCount);

        if ($detailsCount > 0) {
            Queue::assertPushed(ClearStaffCacheJob::class);
        }
    }

    #[Test]
    public function it_trims_optional_string_fields(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user = User::factory()->create(['curp' => 'CURP1234567890123']);

        $rows = [
            [
                'CURP1234567890123',
                 $career->id,
                '19201134',
                 5,
                 '  A  ', // Con espacios
                '  Taller de Programación  ' // Con espacios
            ]
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getInserted());

        $detail = StudentDetail::where('user_id', $user->id)->first();
        $this->assertEquals('A', $detail->group); // Sin espacios
        $this->assertEquals('Taller de Programación', $detail->workshop); // Sin espacios

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_chunk_with_no_valid_curps(): void
    {
        // Arrange
        Queue::fake();

        $rows = [
            [null, '19201134',  5], // Sin CURP
            [null, '19201135',  6], // Sin CURP
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(2, $result->getTotalRows());
        $this->assertEquals(0, $result->getInserted());
        $this->assertEquals(0, $result->getErrorsCount());
        $this->assertGreaterThan(0, $result->getWarningsCount()); // Debería tener warning

        Queue::assertNothingPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_exception_in_chunk_processing_and_continues(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();

        // Crear más de CHUNK_SIZE rows para forzar chunking
        $totalRows = 250;
        $rows = [];

        for ($i = 0; $i < $totalRows; $i++) {
            $rows[] = [
                 'CURP' . str_pad($i, 13, '0', STR_PAD_LEFT),
                $career->id,
                 'CTRL' . str_pad($i, 6, '0', STR_PAD_LEFT),
                 ($i % 10) + 1
            ];
        }

        // No crear usuarios, por lo que todas las CURPs serán inválidas
        // Esto debería generar warnings pero no excepciones

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals($totalRows, $result->getTotalRows());
        $this->assertEquals(0, $result->getInserted());
        $this->assertGreaterThan(0, $result->getErrorsCount());

        Queue::assertNothingPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_inserts_individually_when_batch_insert_fails_with_duplicate_error(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user1 = User::factory()->create(['curp' => 'CURP1234567890123']);
        $user2 = User::factory()->create(['curp' => 'CURP1234567890124']);

        // Crear un detalle existente para user1
        StudentDetail::factory()->create([
            'user_id' => $user1->id,
            'career_id' => $career->id,
            'n_control' => '19201134'
        ]);

        $rows = [
            // Este es duplicado (ya existe)
            [
                'CURP1234567890123',
                 $career->id,
                 '19201134', // Duplicado
                 5,
                 'A'
            ],
            // Este es nuevo
            [
                'CURP1234567890124',
                 $career->id,
                 '19201135',
                 6,
                 'B'
            ]
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(2, $result->getTotalRows());
        $this->assertEquals(1, $result->getInserted()); // Solo el nuevo
        $this->assertEquals(1, $result->getErrorsCount()); // Error por duplicado

        // Verificar que user2 tiene su detalle
        $detail = StudentDetail::where('user_id', $user2->id)->first();
        $this->assertNotNull($detail);
        $this->assertEquals('19201135', $detail->n_control);

        // user1 sigue teniendo solo su detalle original
        $this->assertEquals(1, StudentDetail::where('user_id', $user1->id)->count());

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_special_characters_in_n_control(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user = User::factory()->create(['curp' => 'CURP1234567890123']);

        $rows = [
            [
                 'CURP1234567890123',
                 $career->id,
                 '19-2011-34',
                 5,
                'B+'
            ]
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getInserted());

        $detail = StudentDetail::where('user_id', $user->id)->first();
        $this->assertEquals('19-2011-34', $detail->n_control);
        $this->assertEquals('B+', $detail->group);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_processes_users_in_batches_when_many_curps(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $totalUsers = 1500; // Más que USER_BATCH_SIZE (1000)

        $users = User::factory()->count($totalUsers)->create();
        $rows = [];

        foreach ($users as $index => $user) {
            $rows[] = [
                 $user->curp,
                 $career->id,
                 'CTRL' . str_pad($index, 6, '0', STR_PAD_LEFT),
                 ($index % 10) + 1,
                 'A'
            ];
        }

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals($totalUsers, $result->getTotalRows());
        $this->assertEquals($totalUsers, $result->getInserted());

        $studentDetailsCount = StudentDetail::count();
        $this->assertEquals($totalUsers, $studentDetailsCount);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_includes_timestamps_in_inserted_records(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user = User::factory()->create(['curp' => 'CURP1234567890123']);

        $rows = [
            [
                 'CURP1234567890123',
                 $career->id,
                 '19201134',
                 5
            ]
        ];

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(1, $result->getInserted());

        $detail = StudentDetail::where('user_id', $user->id)->first();
        $this->assertNotNull($detail->created_at);
        $this->assertNotNull($detail->updated_at);

        // Los timestamps deberían ser recientes
        $this->assertTrue($detail->created_at->greaterThan(now()->subMinute()));
        $this->assertTrue($detail->updated_at->greaterThan(now()->subMinute()));

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_dispatches_cache_clear_job_only_once_at_end(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $users = User::factory()->count(250)->create(); // Más que CHUNK_SIZE

        $rows = [];
        foreach ($users as $index => $user) {
            $rows[] = [
                 $user->curp,
                 $career->id,
                 'CTRL' . str_pad($index, 6, '0', STR_PAD_LEFT),
                 ($index % 10) + 1
            ];
        }

        $useCase = app(BulkImportStudentDetailsUseCase::class);

        // Act
        $result = $useCase->execute($rows);

        // Assert
        $this->assertEquals(250, $result->getInserted());

        // Debería dispararse solo una vez, al final
        Queue::assertPushed(ClearStaffCacheJob::class, 1);
        Queue::assertPushed(ClearStaffCacheJob::class, function ($job) {
            return $job->queue === 'cache';
        });
    }

}
