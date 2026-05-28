<?php

namespace Tests\Unit\Domain;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class BaseDomainTestCase extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic unit test example.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }
    protected function assertDateEquality(
        CarbonImmutable $expected,
        string $actualDateString
    ): void {
        $actual = CarbonImmutable::parse($actualDateString);
        $this->assertTrue(
            $expected->eq($actual),
            sprintf(
                'Dates do not match. Expected: %s, Actual: %s',
                $expected->toIso8601String(),
                $actual->toIso8601String()
            )
        );
    }
}
