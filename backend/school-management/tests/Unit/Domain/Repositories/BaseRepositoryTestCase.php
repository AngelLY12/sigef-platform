<?php

namespace Tests\Unit\Domain\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


abstract class BaseRepositoryTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * The repository instance being tested
     */
    protected $repository;

    /**
     * The interface class being implemented
     */
    protected string $interfaceClass;

    protected bool $needsDatabase = false;

    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'setUpRepository')) {
            $this->setUpRepository();
        }
    }

    /**
     * Assert that a method exists in the repository
     */
    protected function assertMethodExists(string $methodName): void
    {
        $this->assertTrue(
            method_exists($this->repository, $methodName),
            "Method {$methodName} does not exist in repository"
        );
    }

    /**
     * Assert that a method has the correct return type
     */
    protected function assertMethodReturnType(string $methodName, string $expectedType): void
    {
        $reflection = new \ReflectionMethod($this->repository, $methodName);
        $returnType = $reflection->getReturnType();

        $this->assertNotNull(
            $returnType,
            "Method {$methodName} does not have a return type declaration"
        );

        $this->assertEquals(
            $expectedType,
            $returnType->getName(),
            "Method {$methodName} should return {$expectedType}"
        );
    }

    /**
     * Assert that a parameter has the correct type
     */
    protected function assertMethodParameterType(
        string $methodName,
        string $expectedType,
        int $parameterIndex = 0
    ): void {
        $reflection = new \ReflectionMethod($this->repository, $methodName);
        $parameters = $reflection->getParameters();

        $this->assertArrayHasKey(
            $parameterIndex,
            $parameters,
            "Parameter at index {$parameterIndex} does not exist in method '{$methodName}'"
        );

        $parameter = $parameters[$parameterIndex];
        $type = $parameter->getType();

        if ($expectedType === 'mixed' && $type === null) {
            $this->assertTrue(true); // mixed no tiene tipo explícito
            return;
        }

        $this->assertNotNull(
            $type,
            "Parameter '{$parameter->getName()}' in method '{$methodName}' does not have a type declaration"
        );

        if ($type instanceof \ReflectionUnionType) {
            $types = $type->getTypes();
            $typeNames = array_map(fn($t) => $t->getName(), $types);
            $this->assertContains(
                $expectedType,
                $typeNames,
                "Parameter '{$parameter->getName()}' in method '{$methodName}' should accept {$expectedType}"
            );
        } else {
            $actualType = $type->getName();
            $this->assertEquals(
                $expectedType,
                $actualType,
                "Parameter '{$parameter->getName()}' in method '{$methodName}' should be {$expectedType}, but is {$actualType}"
            );
        }
    }

    /**
     * Assert that a method has the correct number of parameters
     */
    protected function assertMethodParameterCount(string $methodName, int $expectedCount): void
    {
        $reflection = new \ReflectionMethod($this->repository, $methodName);
        $parameters = $reflection->getParameters();

        $this->assertCount(
            $expectedCount,
            $parameters,
            "Method {$methodName} should have {$expectedCount} parameters"
        );
    }

    /**
     * Create a mock DTO for testing
     */
    protected function createMockDto(string $dtoClass, array $properties = [])
    {
        $mock = $this->getMockBuilder($dtoClass)
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($properties as $property => $value) {
            if (property_exists($mock, $property)) {
                $mock->$property = $value;
            }
        }

        return $mock;
    }

    /**
     * Create a test entity instance
     */
    protected function createTestEntity(string $entityClass, array $attributes = [])
    {
        $entity = new $entityClass();

        foreach ($attributes as $key => $value) {
            if (property_exists($entity, $key)) {
                $entity->$key = $value;
            }
        }

        return $entity;
    }

    /**
     * Assert that repository implements the correct interface
     */
    protected function assertImplementsInterface(string $interfaceClass): void
    {
        $this->assertInstanceOf(
            $interfaceClass,
            $this->repository,
            "Repository must implement {$interfaceClass}"
        );
    }

}
