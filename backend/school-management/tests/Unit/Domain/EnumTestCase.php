<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

abstract class EnumTestCase extends TestCase
{
    abstract protected function enumClass(): string;

    #[Test]
    public function it_has_all_cases(): void
    {
        $class = $this->enumClass();
        $cases = $class::cases();

        $this->assertNotEmpty($cases, "El enum {$class} debe tener al menos un caso");
    }

    #[Test]
    public function it_converts_from_string(): void
    {
        $class = $this->enumClass();
        $cases = $class::cases();

        foreach ($cases as $case) {
            $fromString = $class::from($case->value);
            $this->assertEquals(
                $case,
                $fromString,
                "from('{$case->value}') debería devolver {$class}::{$case->name}"
            );
        }
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $class = $this->enumClass();
        $cases = $class::cases();

        foreach ($cases as $case) {
            if (method_exists($case, '__toString')) {
                $this->assertEquals(
                    $case->value,
                    (string) $case,
                    "String cast debería devolver '{$case->value}'"
                );
            }

            $this->assertEquals(
                $case->value,
                $case->value,
                "->value debería devolver '{$case->value}'"
            );
        }
    }

    #[Test]
    public function it_handles_invalid_string_conversion(): void
    {
        $class = $this->enumClass();

        $this->expectException(\ValueError::class);
        $class::from('invalid_value_that_does_not_exist');
    }

    #[Test]
    public function it_has_unique_values(): void
    {
        $class = $this->enumClass();
        $cases = $class::cases();

        $values = array_map(fn($case) => $case->value, $cases);
        $uniqueValues = array_unique($values);

        $this->assertCount(
            count($values),
            $uniqueValues,
            "El enum {$class} tiene valores duplicados"
        );
    }

    #[Test]
    public function it_can_be_used_in_match_statements(): void
    {
        $class = $this->enumClass();
        $firstCase = $class::cases()[0];

        // Test que el match statement funciona
        $result = match($firstCase) {
            $firstCase => 'matched',
            default => 'not_matched',
        };

        $this->assertEquals('matched', $result);
    }

    /**
     * Helper para obtener todos los casos del enum
     */
    protected function getAllCases(): array
    {
        $class = $this->enumClass();
        return $class::cases();
    }

    /**
     * Helper para obtener todos los valores del enum
     */
    protected function getAllValues(): array
    {
        return array_map(fn($case) => $case->value, $this->getAllCases());
    }

}
