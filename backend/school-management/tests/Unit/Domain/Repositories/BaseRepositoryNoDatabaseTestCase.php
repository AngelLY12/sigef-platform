<?php

namespace Tests\Unit\Domain\Repositories;

use Tests\TestCase;

abstract class BaseRepositoryNoDatabaseTestCase extends TestCase
{
    /**
     * The repository instance being tested
     */
    protected $repository;

    /**
     * The interface class being implemented
     */
    protected string $interfaceClass;

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

}
