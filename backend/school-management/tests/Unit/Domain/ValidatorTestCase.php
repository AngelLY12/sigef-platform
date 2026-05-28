<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

abstract class ValidatorTestCase extends TestCase
{
    /**
     * Nombre completo de la clase del validador
     */
    abstract protected function validatorClass(): string;

    /**
     * Método a testear
     */
    abstract protected function validatorMethod(): string;

    /**
     * Crear datos válidos para el validador
     */
    abstract protected function createValidData();

    /**
     * Crear datos inválidos para el validador
     */
    abstract protected function createInvalidData(): array;

    #[Test]
    public function it_accepts_valid_data(): void
    {
        $validatorClass = $this->validatorClass();
        $method = $this->validatorMethod();
        $validData = $this->createValidData();

        // Si no lanza excepción, pasa la validación
        try {
            if (is_array($validData)) {
                $validatorClass::$method(...$validData);
            } else {
                $validatorClass::$method($validData);
            }

            $this->assertTrue(true, 'Datos válidos deberían pasar la validación');
        } catch (\Exception $e) {
            $this->fail("Datos válidos no deberían lanzar excepción: " . $e->getMessage());
        }
    }

    /**
     * @test
     * @dataProvider invalidDataProvider
     */
    #[Test]
    #[DataProvider('invalidDataProvider')]
    public function it_rejects_invalid_data($invalidData, string $expectedException, string $expectedMessage): void
    {
        $validatorClass = $this->validatorClass();
        $method = $this->validatorMethod();

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        if (is_array($invalidData)) {
            $validatorClass::$method(...$invalidData);
        } else {
            $validatorClass::$method($invalidData);
        }
    }

    /**
     * Proveedor de datos inválidos
     */
    public function invalidDataProvider(): array
    {
        return $this->createInvalidData();
    }

    /**
     * Assert que una validación pasa sin excepción
     */
    protected function assertValidationPasses(callable $validation): void
    {
        try {
            $validation();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail("Validación debería pasar pero lanzó: " . $e->getMessage());
        }
    }

    /**
     * Assert que una validación falla con excepción específica
     */
    protected function assertValidationFails(
        callable $validation,
        string $expectedException,
        string $expectedMessage = ''
    ): void {
        try {
            $validation();
            $this->fail("Validación debería fallar pero pasó");
        } catch (\Exception $e) {
            $this->assertInstanceOf($expectedException, $e);
            if ($expectedMessage) {
                $this->assertStringContainsString($expectedMessage, $e->getMessage());
            }
        }
    }

    /**
     * Crear un mock de entidad con propiedades específicas
     */
    protected function createEntityMockWithProperties(string $entityClass, array $properties)
    {
        $mock = $this->createMock($entityClass);

        foreach ($properties as $property => $value) {
            $mock->$property = $value;

            // Si es un método, mockearlo
            if (method_exists($mock, $property)) {
                $mock->method($property)->willReturn($value);
            }
        }

        return $mock;
    }

}
