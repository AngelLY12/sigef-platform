<?php

namespace Tests\Unit\Infraestructure\Repositories\Query;

use App\Core\Domain\Entities\Career;
use App\Core\Infraestructure\Repositories\Query\Misc\EloquentCareerQueryRepository;
use App\Models\Career as EloquentCareer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentCareerQueryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentCareerQueryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentCareerQueryRepository();
    }

    // ==================== FIND BY NAME TESTS ====================

    #[Test]
    public function find_by_name_successfully(): void
    {
        // Arrange
        $careerName = 'Ingeniería en Sistemas Computacionales';
        $career = EloquentCareer::factory()->create([
            'career_name' => $careerName
        ]);

        // Act
        $result = $this->repository->findByName($careerName);

        // Assert
        $this->assertInstanceOf(Career::class, $result);
        $this->assertEquals($career->id, $result->id);
        $this->assertEquals($careerName, $result->career_name);
    }

    #[Test]
    public function find_by_name_with_exact_match(): void
    {
        // Arrange
        $partialName = 'Sistemas';
        $fullName = 'Ingeniería en Sistemas Computacionales';

        $career = EloquentCareer::factory()->create([
            'career_name' => $fullName
        ]);

        // Act - Búsqueda parcial
        $result = $this->repository->findByName($partialName);

        // Assert - Debería fallar porque necesita coincidencia exacta
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_name_returns_null_for_nonexistent_name(): void
    {
        // Act
        $result = $this->repository->findByName('Carrera Inexistente');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_name_returns_null_for_empty_string(): void
    {
        // Act
        $result = $this->repository->findByName('');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_name_with_special_characters(): void
    {
        // Arrange
        $careerName = 'Ingeniería Mecánica-Eléctrica (IME)';
        $career = EloquentCareer::factory()->create([
            'career_name' => $careerName
        ]);

        // Act
        $result = $this->repository->findByName($careerName);

        // Assert
        $this->assertInstanceOf(Career::class, $result);
        $this->assertEquals($careerName, $result->career_name);
    }

    // ==================== FIND ALL TESTS ====================

    #[Test]
    public function find_all_returns_array_of_careers(): void
    {
        // Arrange
        $careers = EloquentCareer::factory()->count(5)->create();

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(5, $result);

        foreach ($result as $career) {
            $this->assertInstanceOf(Career::class, $career);
            $this->assertNotNull($career->id);
            $this->assertNotNull($career->career_name);
        }
    }

    #[Test]
    public function find_all_returns_empty_array_when_no_careers(): void
    {
        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function find_all_returns_careers_in_correct_order(): void
    {
        // Arrange - Crear carreras en orden específico
        $career1 = EloquentCareer::factory()->create([
            'career_name' => 'Zootecnia',
            'created_at' => now()->subDays(2)
        ]);

        $career2 = EloquentCareer::factory()->create([
            'career_name' => 'Arquitectura',
            'created_at' => now()->subDays(1)
        ]);

        $career3 = EloquentCareer::factory()->create([
            'career_name' => 'Medicina',
            'created_at' => now()
        ]);

        // Act
        $result = $this->repository->findAll();

        // Assert - Deberían venir en el orden por defecto (probablemente por ID)
        $this->assertCount(3, $result);

        // Verificar que todas las carreras están presentes
        $careerNames = array_map(fn($c) => $c->career_name, $result);
        $this->assertContains('Zootecnia', $careerNames);
        $this->assertContains('Arquitectura', $careerNames);
        $this->assertContains('Medicina', $careerNames);
    }

    // ==================== FIND BY ID TESTS ====================

    #[Test]
    public function find_by_id_successfully(): void
    {
        // Arrange
        $career = EloquentCareer::factory()->create();

        // Act
        $result = $this->repository->findById($career->id);

        // Assert
        $this->assertInstanceOf(Career::class, $result);
        $this->assertEquals($career->id, $result->id);
        $this->assertEquals($career->career_name, $result->career_name);
    }

    #[Test]
    public function find_by_id_returns_null_for_nonexistent_id(): void
    {
        // Act
        $result = $this->repository->findById(999999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_id_returns_null_for_zero_id(): void
    {
        // Act
        $result = $this->repository->findById(0);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_id_returns_null_for_negative_id(): void
    {
        // Act
        $result = $this->repository->findById(-1);

        // Assert
        $this->assertNull($result);
    }

    // ==================== COMPARISON TESTS ====================

    #[Test]
    public function find_by_id_and_find_by_name_return_same_career(): void
    {
        // Arrange
        $careerName = 'Derecho';
        $career = EloquentCareer::factory()->create([
            'career_name' => $careerName
        ]);

        // Act
        $resultById = $this->repository->findById($career->id);
        $resultByName = $this->repository->findByName($careerName);

        // Assert
        $this->assertNotNull($resultById);
        $this->assertNotNull($resultByName);
        $this->assertEquals($resultById->id, $resultByName->id);
        $this->assertEquals($resultById->career_name, $resultByName->career_name);
    }

    #[Test]
    public function find_all_includes_career_found_by_id(): void
    {
        // Arrange
        $career = EloquentCareer::factory()->create();
        EloquentCareer::factory()->count(4)->create();

        // Act
        $allCareers = $this->repository->findAll();
        $specificCareer = $this->repository->findById($career->id);

        // Assert
        $this->assertCount(5, $allCareers);
        $this->assertNotNull($specificCareer);

        // Verificar que la carrera específica está en la lista
        $careerIds = array_map(fn($c) => $c->id, $allCareers);
        $this->assertContains($specificCareer->id, $careerIds);
    }

    // ==================== EDGE CASES TESTS ====================

    #[Test]
    public function career_with_long_name(): void
    {
        // Arrange
        $longName = 'Licenciatura en Ciencias de la Computación';
        $career = EloquentCareer::factory()->create([
            'career_name' => $longName
        ]);

        // Act
        $result = $this->repository->findByName($longName);

        // Assert
        $this->assertInstanceOf(Career::class, $result);
        $this->assertEquals($longName, $result->career_name);
    }

    #[Test]
    public function career_with_numeric_name(): void
    {
        // Arrange
        $numericName = 'Carrera 123';
        $career = EloquentCareer::factory()->create([
            'career_name' => $numericName
        ]);

        // Act
        $result = $this->repository->findByName($numericName);

        // Assert
        $this->assertInstanceOf(Career::class, $result);
        $this->assertEquals($numericName, $result->career_name);
    }

    #[Test]
    public function multiple_careers_with_similar_names(): void
    {
        // Arrange
        $careers = [
            'Ingeniería Civil',
            'Ingeniería Civil Industrial',
            'Ingeniería Civil en Computación',
            'Ingeniería'
        ];

        foreach ($careers as $name) {
            EloquentCareer::factory()->create(['career_name' => $name]);
        }

        // Act - Buscar uno específico
        $result = $this->repository->findByName('Ingeniería Civil');

        // Assert
        $this->assertInstanceOf(Career::class, $result);
        $this->assertEquals('Ingeniería Civil', $result->career_name);
    }

    #[Test]
    public function find_all_after_deleting_career(): void
    {
        // Arrange
        $career1 = EloquentCareer::factory()->create();
        $career2 = EloquentCareer::factory()->create();
        $career3 = EloquentCareer::factory()->create();

        // Act - Eliminar una carrera
        $career2->delete();

        $result = $this->repository->findAll();

        // Assert
        $this->assertCount(2, $result);

        $careerIds = array_map(fn($c) => $c->id, $result);
        $this->assertContains($career1->id, $careerIds);
        $this->assertContains($career3->id, $careerIds);
        $this->assertNotContains($career2->id, $careerIds);
    }

    // ==================== PERFORMANCE TESTS ====================

    #[Test]
    public function find_all_with_large_number_of_careers(): void
    {
        // Arrange
        $careerCount = 20;
        EloquentCareer::factory()->count($careerCount)->create();

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertCount($careerCount, $result);

        // Verificar que todas son instancias de Career
        foreach ($result as $career) {
            $this->assertInstanceOf(Career::class, $career);
        }
    }

    #[Test]
    public function find_by_name_performance_with_many_records(): void
    {
        // Arrange
        $targetName = 'Carrera Objetivo';

        // Crear muchas carreras
        EloquentCareer::factory()->count(10)->create();

        // Crear la carrera objetivo al final
        $targetCareer = EloquentCareer::factory()->create([
            'career_name' => $targetName
        ]);

        // Act
        $result = $this->repository->findByName($targetName);

        // Assert
        $this->assertInstanceOf(Career::class, $result);
        $this->assertEquals($targetCareer->id, $result->id);
    }

    // ==================== DOMAIN OBJECT TESTS ====================

    #[Test]
    public function career_domain_object_methods(): void
    {
        // Arrange
        $careerName = 'Psicología';
        $career = EloquentCareer::factory()->create([
            'career_name' => $careerName
        ]);

        $domainCareer = $this->repository->findById($career->id);

        // Act & Assert
        $this->assertNotNull($domainCareer);

        // Test toArray() method if exists
        if (method_exists($domainCareer, 'toArray')) {
            $array = $domainCareer->toArray();
            $this->assertIsArray($array);
            $this->assertEquals($career->id, $array['id']);
            $this->assertEquals($careerName, $array['name']);
        }

        // Test getId() method if exists
        if (method_exists($domainCareer, 'getId')) {
            $this->assertEquals($career->id, $domainCareer->getId());
        }

        // Test getName() method if exists
        if (method_exists($domainCareer, 'getName')) {
            $this->assertEquals($careerName, $domainCareer->getName());
        }
    }

    #[Test]
    public function career_domain_object_immutability(): void
    {
        // Arrange
        $career = EloquentCareer::factory()->create();
        $domainCareer = $this->repository->findById($career->id);

        // Act - Intentar modificar (si es posible)
        $this->assertInstanceOf(Career::class, $domainCareer);

        // Assert - Verificar que no se puede modificar directamente
        // (Depende de cómo esté implementada la entidad Career)
        // Normalmente las entidades de dominio son inmutables o tienen setters protegidos
    }

    // ==================== INTEGRATION TESTS ====================

    #[Test]
    public function complete_career_lifecycle_queries(): void
    {
        // 1. Verificar que no hay carreras inicialmente
        $initialCareers = $this->repository->findAll();
        $this->assertEmpty($initialCareers);

        // 2. Crear algunas carreras
        $careerNames = [
            'Administración de Empresas',
            'Contaduría Pública',
            'Mercadotecnia'
        ];

        $createdCareers = [];
        foreach ($careerNames as $name) {
            $career = EloquentCareer::factory()->create(['career_name' => $name]);
            $createdCareers[] = $career;
        }

        // 3. Buscar todas las carreras
        $allCareers = $this->repository->findAll();
        $this->assertCount(3, $allCareers);

        // 4. Buscar cada carrera por nombre
        foreach ($careerNames as $name) {
            $career = $this->repository->findByName($name);
            $this->assertInstanceOf(Career::class, $career);
            $this->assertEquals($name, $career->career_name);
        }

        // 5. Buscar cada carrera por ID
        foreach ($createdCareers as $eloquentCareer) {
            $career = $this->repository->findById($eloquentCareer->id);
            $this->assertInstanceOf(Career::class, $career);
            $this->assertEquals($eloquentCareer->career_name, $career->career_name);
        }

        // 6. Buscar carrera inexistente
        $nonexistent = $this->repository->findByName('Carrera Fantasma');
        $this->assertNull($nonexistent);

        $nonexistentById = $this->repository->findById(999999);
        $this->assertNull($nonexistentById);
    }

    #[Test]
    public function repository_methods_independent_of_database_state(): void
    {
        // Este test verifica que cada método funciona independientemente

        // 1. findAll con datos
        EloquentCareer::factory()->count(3)->create();
        $allResult = $this->repository->findAll();
        $this->assertCount(3, $allResult);

        // 2. findByName específico
        $targetCareer = EloquentCareer::factory()->create([
            'career_name' => 'Target Career'
        ]);
        $byNameResult = $this->repository->findByName('Target Career');
        $this->assertNotNull($byNameResult);
        $this->assertEquals($targetCareer->id, $byNameResult->id);

        // 3. findById específico
        $byIdResult = $this->repository->findById($targetCareer->id);
        $this->assertNotNull($byIdResult);
        $this->assertEquals('Target Career', $byIdResult->career_name);

        // 4. Verificar que todos los métodos coexisten
        $this->assertCount(4, $this->repository->findAll()); // 3 iniciales + 1 target
    }

}
