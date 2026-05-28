<?php

namespace Tests\Unit\Domain\Repositories\Query;

use Tests\Stubs\Repositories\Query\ParentStudentQueryRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Query\User\ParentStudentQueryRepInterface;
use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;
use PHPUnit\Framework\Attributes\Test;

class ParentStudentQueryRepInterfaceTest extends BaseRepositoryTestCase
{
    protected string $interfaceClass = ParentStudentQueryRepInterface::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ParentStudentQueryRepStub();
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
        $methods = ['getStudentsOfParent', 'getParentsOfStudent', 'exists'];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function getStudentsOfParent_returns_response_when_parent_has_students(): void
    {
        $childrenData = [
            ['id' => 1, 'fullName' => 'Juan Pérez'],
            ['id' => 2, 'fullName' => 'María López'],
        ];

        $response = new ParentChildrenResponse(
            parentId: 1,
            parentName: 'Carlos García',
            childrenData: $childrenData
        );

        $this->repository->setNextGetStudentsOfParentResult($response);

        $result = $this->repository->getStudentsOfParent(1);

        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertEquals(1, $result->parentId);
        $this->assertEquals('Carlos García', $result->parentName);
        $this->assertIsArray($result->childrenData);
        $this->assertCount(2, $result->childrenData);
        $this->assertEquals('Juan Pérez', $result->childrenData[0]['fullName']);
    }

    #[Test]
    public function getStudentsOfParent_returns_null_when_parent_has_no_students(): void
    {
        $this->repository->setNextGetStudentsOfParentResult(null);

        $result = $this->repository->getStudentsOfParent(999);

        $this->assertNull($result);
    }

    #[Test]
    public function getParentsOfStudent_returns_response_when_student_has_parents(): void
    {
        $parentsData = [
            ['id' => 1, 'fullName' => 'Carlos García'],
            ['id' => 2, 'fullName' => 'Ana Martínez'],
        ];

        $response = new StudentParentsResponse(
            studentId: 3,
            studentName: 'Juan Pérez',
            parentsData: $parentsData
        );

        $this->repository->setNextGetParentsOfStudentResult($response);

        $result = $this->repository->getParentsOfStudent(3);

        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals(3, $result->studentId);
        $this->assertEquals('Juan Pérez', $result->studentName);
        $this->assertIsArray($result->parentsData);
        $this->assertCount(2, $result->parentsData);
        $this->assertEquals('Carlos García', $result->parentsData[0]['fullName']);
    }

    #[Test]
    public function getParentsOfStudent_returns_null_when_student_has_no_parents(): void
    {
        $this->repository->setNextGetParentsOfStudentResult(null);

        $result = $this->repository->getParentsOfStudent(999);

        $this->assertNull($result);
    }

    #[Test]
    public function exists_returns_true_when_relationship_exists(): void
    {
        $this->repository->setNextExistsResult(true);

        $result = $this->repository->exists(1, 2);

        $this->assertTrue($result);
    }

    #[Test]
    public function exists_returns_false_when_relationship_does_not_exist(): void
    {
        $this->repository->setNextExistsResult(false);

        $result = $this->repository->exists(1, 999);

        $this->assertFalse($result);
    }

    #[Test]
    public function methods_have_correct_signatures(): void
    {
        $this->assertMethodParameterType('getStudentsOfParent', 'int');
        $this->assertMethodParameterType('getParentsOfStudent', 'int');
        $this->assertMethodParameterCount('exists', 2);

        $this->assertMethodReturnType('getStudentsOfParent', ParentChildrenResponse::class);
        $this->assertMethodReturnType('getParentsOfStudent', StudentParentsResponse::class);
        $this->assertMethodReturnType('exists', 'bool');
    }

    #[Test]
    public function getStudentsOfParent_with_empty_children_data(): void
    {
        $response = new ParentChildrenResponse(
            parentId: 1,
            parentName: 'Carlos García',
            childrenData: []
        );

        $this->repository->setNextGetStudentsOfParentResult($response);

        $result = $this->repository->getStudentsOfParent(1);

        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertEmpty($result->childrenData);
    }

    #[Test]
    public function getParentsOfStudent_with_empty_parents_data(): void
    {
        $response = new StudentParentsResponse(
            studentId: 1,
            studentName: 'Juan Pérez',
            parentsData: []
        );

        $this->repository->setNextGetParentsOfStudentResult($response);

        $result = $this->repository->getParentsOfStudent(1);

        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEmpty($result->parentsData);
    }

    #[Test]
    public function exists_with_different_parent_student_combinations(): void
    {
        // Test combinación 1
        $this->repository->setNextExistsResult(true);
        $result1 = $this->repository->exists(1, 2);
        $this->assertTrue($result1);

        // Test combinación 2
        $this->repository->setNextExistsResult(false);
        $result2 = $this->repository->exists(3, 4);
        $this->assertFalse($result2);
    }
}
