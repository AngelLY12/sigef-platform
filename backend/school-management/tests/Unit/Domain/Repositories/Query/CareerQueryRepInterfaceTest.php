<?php

namespace Tests\Unit\Domain\Repositories\Query;

use Tests\Stubs\Repositories\Query\CareerQueryRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Query\Misc\CareerQueryRepInterface;
use App\Core\Domain\Entities\Career;
use PHPUnit\Framework\Attributes\Test;

class CareerQueryRepInterfaceTest extends BaseRepositoryTestCase
{
    protected string $interfaceClass = CareerQueryRepInterface::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CareerQueryRepStub();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository);
        $this->assertImplementsInterface($this->interfaceClass);
    }

    #[Test]
    public function it_has_all_required_methods(): void
    {
        $methods = ['findById', 'findByName', 'findAll'];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function findById_returns_career_when_found(): void
    {
        $career = new Career('Matemáticas', 1);
        $this->repository->setNextFindByIdResult($career);

        $result = $this->repository->findById(1);

        $this->assertInstanceOf(Career::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Matemáticas', $result->career_name);
    }

    #[Test]
    public function findById_returns_null_when_not_found(): void
    {
        $this->repository->setNextFindByIdResult(null);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    #[Test]
    public function findByName_returns_career_when_found(): void
    {
        $career = new Career('Ingeniería', 2);
        $this->repository->setNextFindByNameResult($career);

        $result = $this->repository->findByName('Ingeniería');

        $this->assertInstanceOf(Career::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('Ingeniería', $result->career_name);
    }

    #[Test]
    public function findByName_returns_null_when_not_found(): void
    {
        $this->repository->setNextFindByNameResult(null);

        $result = $this->repository->findByName('No existe');

        $this->assertNull($result);
    }

    #[Test]
    public function findAll_returns_array_when_careers_exist(): void
    {
        $careers = [
            new Career('Matemáticas', 1),
            new Career('Física', 2),
            new Career('Química', 3),
        ];
        $this->repository->setNextFindAllResult($careers);

        $result = $this->repository->findAll();

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(Career::class, $result);
    }

    #[Test]
    public function findAll_returns_null_when_no_careers(): void
    {
        $this->repository->setNextFindAllResult(null);

        $result = $this->repository->findAll();

        $this->assertNull($result);
    }

    #[Test]
    public function methods_have_correct_signatures(): void
    {
        $this->assertMethodParameterType('findById', 'int');
        $this->assertMethodParameterType('findByName', 'string');
        $this->assertMethodParameterCount('findAll', 0);

        $this->assertMethodReturnType('findById', Career::class);
        $this->assertMethodReturnType('findByName', Career::class);
        $this->assertMethodReturnType('findAll', 'array');
    }
}
