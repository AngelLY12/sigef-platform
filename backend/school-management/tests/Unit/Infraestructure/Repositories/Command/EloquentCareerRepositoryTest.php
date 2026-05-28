<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Domain\Entities\Career as DomainCareer;
use App\Core\Infraestructure\Repositories\Command\Misc\EloquentCareerRepository;
use App\Models\Career;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentCareerRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private EloquentCareerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentCareerRepository();

        // Limpiar antes de cada test
        Career::query()->delete();
    }

    #[Test]
    public function create_career_saves_to_database_and_returns_domain_entity(): void
    {
        // Arrange
        $domainCareer = new DomainCareer(
            career_name: 'Ingeniería en Sistemas'
        );

        // Act
        $result = $this->repository->create($domainCareer);

        // Assert
        $this->assertInstanceOf(DomainCareer::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals('Ingeniería en Sistemas', $result->career_name);

        // Verify in database
        $this->assertDatabaseHas('careers', [
            'career_name' => 'Ingeniería en Sistemas'
        ]);

        // Verify the ID matches
        $dbCareer = Career::where('career_name', 'Ingeniería en Sistemas')->first();
        $this->assertEquals($dbCareer->id, $result->id);
    }

    #[Test]
    public function delete_career_removes_from_database(): void
    {
        // Arrange - Usando factory
        $career = Career::factory()->create([
            'career_name' => 'Derecho'
        ]);

        // Act
        $this->repository->delete($career->id);

        // Assert
        $this->assertDatabaseMissing('careers', [
            'id' => $career->id,
            'career_name' => 'Derecho'
        ]);
    }

    #[Test]
    public function delete_career_throws_exception_when_not_found(): void
    {
        // Arrange
        $nonExistentId = 999999;

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->repository->delete($nonExistentId);
    }

    #[Test]
    public function update_career_modifies_fields_and_returns_domain_entity(): void
    {
        // Arrange - Usando factory
        $career = Career::factory()->create([
            'career_name' => 'Arquitectura'
        ]);

        $fields = [
            'career_name' => 'Arquitectura y Urbanismo'
        ];

        // Act
        $result = $this->repository->update($career->id, $fields);

        // Assert
        $this->assertInstanceOf(DomainCareer::class, $result);
        $this->assertEquals($career->id, $result->id);
        $this->assertEquals('Arquitectura y Urbanismo', $result->career_name);

        // Verify in database
        $this->assertDatabaseHas('careers', [
            'id' => $career->id,
            'career_name' => 'Arquitectura y Urbanismo'
        ]);
    }

    #[Test]
    public function update_career_throws_exception_when_not_found(): void
    {
        // Arrange
        $nonExistentId = 999999;
        $fields = ['career_name' => 'Nueva Carrera'];

        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->repository->update($nonExistentId, $fields);
    }

    #[Test]
    public function create_career_with_factory_generated_name(): void
    {
        // Arrange
        // Usar un nombre generado por el factory
        $careerFromFactory = Career::factory()->make();
        $domainCareer = new DomainCareer(
            career_name: $careerFromFactory->career_name
        );

        // Act
        $result = $this->repository->create($domainCareer);

        // Assert
        $this->assertNotNull($result->id);
        $this->assertEquals($careerFromFactory->career_name, $result->career_name);
        $this->assertDatabaseHas('careers', [
            'career_name' => $careerFromFactory->career_name
        ]);
    }

    #[Test]
    public function update_career_with_factory_data(): void
    {
        // Arrange
        $career = Career::factory()->create();
        $newCareerData = Career::factory()->make();

        $fields = [
            'career_name' => $newCareerData->career_name
        ];

        // Act
        $result = $this->repository->update($career->id, $fields);

        // Assert
        $this->assertEquals($newCareerData->career_name, $result->career_name);
        $this->assertDatabaseHas('careers', [
            'id' => $career->id,
            'career_name' => $newCareerData->career_name
        ]);
    }

    #[Test]
    public function multiple_career_operations_with_factories(): void
    {
        // Arrange - Crear varias carreras con factory
        $career1 = Career::factory()->create(['career_name' => 'Medicina']);
        $career2 = Career::factory()->create(['career_name' => 'Ingeniería']);
        $career3 = Career::factory()->create(['career_name' => 'Derecho']);

        // Act 1 - Eliminar una
        $this->repository->delete($career2->id);

        // Assert 1
        $this->assertDatabaseMissing('careers', ['id' => $career2->id]);
        $this->assertDatabaseHas('careers', ['id' => $career1->id]);
        $this->assertDatabaseHas('careers', ['id' => $career3->id]);

        // Act 2 - Actualizar otra
        $updated = $this->repository->update($career3->id, [
            'career_name' => 'Derecho Internacional'
        ]);

        // Assert 2
        $this->assertEquals('Derecho Internacional', $updated->career_name);
        $this->assertDatabaseHas('careers', [
            'id' => $career3->id,
            'career_name' => 'Derecho Internacional'
        ]);
    }

}
