<?php

namespace Tests\Unit\Application\Mappers;

use App\Core\Application\Mappers\ParentInviteMapper;
use App\Core\Domain\Entities\ParentInvite;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ParentInviteMapperTest extends TestCase
{
    #[Test]
    public function to_domain_creates_parent_invite_with_correct_data(): void
    {
        // Arrange
        Carbon::setTestNow('2024-01-15 10:00:00');

        $data = [
            'studentId' => 123,
            'parentEmail' => 'parent@example.com',
            'createdBy' => 456,
        ];

        // Mock Str::uuid para predecible testing
        $expectedUuid = '123e4567-e89b-12d3-a456-426614174000';
        Str::createUuidsUsing(fn() => $expectedUuid);

        // Act
        $result = ParentInviteMapper::toDomain($data);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals(123, $result->studentId);
        $this->assertEquals('parent@example.com', $result->email);
        $this->assertEquals($expectedUuid, $result->token);
        $this->assertEquals(Carbon::parse('2024-01-17 10:00:00'), $result->expiresAt); // 48 horas después
        $this->assertEquals(456, $result->createdBy);
        $this->assertNull($result->usedAt);

        // Clean up UUID mocking
        Str::createUuidsNormally();
    }

    #[Test]
    public function to_domain_always_generates_unique_tokens(): void
    {
        // Arrange
        $data = [
            'studentId' => 1,
            'parentEmail' => 'test@example.com',
            'createdBy' => 1,
        ];

        // Act - Generar múltiples invites
        $invites = [];
        for ($i = 0; $i < 5; $i++) {
            $invites[] = ParentInviteMapper::toDomain($data);
        }

        // Assert - Cada token debe ser único
        $tokens = array_map(fn($invite) => $invite->token, $invites);
        $uniqueTokens = array_unique($tokens);

        $this->assertCount(5, $uniqueTokens, 'All tokens should be unique');
    }

    #[Test]
    public function to_domain_expires_at_is_48_hours_from_now(): void
    {
        // Arrange
        $testTimes = [
            '2024-01-01 00:00:00',
            '2024-06-15 12:30:00',
            '2024-12-31 23:59:59',
        ];

        foreach ($testTimes as $testTime) {
            Carbon::setTestNow($testTime);

            $data = [
                'studentId' => 1,
                'parentEmail' => 'test@example.com',
                'createdBy' => 1,
            ];

            // Act
            $result = ParentInviteMapper::toDomain($data);

            // Assert
            $expectedExpiresAt = Carbon::parse($testTime)->addHours(48);
            $this->assertEquals(
                $expectedExpiresAt,
                $result->expiresAt,
                "Failed for test time: {$testTime}"
            );
        }
    }

    #[Test]
    public function to_domain_handles_missing_optional_fields(): void
    {
        // Arrange - Solo campos requeridos
        $data = [
            'studentId' => 999,
            'parentEmail' => 'required@example.com',
            'createdBy' => 888,
        ];

        // Act
        $result = ParentInviteMapper::toDomain($data);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals(999, $result->studentId);
        $this->assertEquals('required@example.com', $result->email);
        $this->assertEquals(888, $result->createdBy);
        $this->assertNotNull($result->token);
        $this->assertNotNull($result->expiresAt);
        $this->assertNull($result->usedAt);
    }

    #[Test]
    public function to_domain_token_is_valid_uuid(): void
    {
        // Arrange
        $data = [
            'studentId' => 1,
            'parentEmail' => 'test@example.com',
            'createdBy' => 1,
        ];

        // Act
        $result = ParentInviteMapper::toDomain($data);

        // Assert
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $result->token,
            'Token should be a valid UUID'
        );
    }

    #[Test]
    public function to_domain_does_not_accept_extra_fields(): void
    {
        // Arrange - Datos con campos extra
        $data = [
            'studentId' => 1,
            'parentEmail' => 'test@example.com',
            'createdBy' => 1,
            'extraField' => 'should be ignored',
            'anotherExtra' => 123,
        ];

        // Act
        $result = ParentInviteMapper::toDomain($data);

        // Assert - No debería haber errores, solo usa los campos necesarios
        $this->assertInstanceOf(ParentInvite::class, $result);
        // Verificar que los campos requeridos están presentes
        $this->assertEquals(1, $result->studentId);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals(1, $result->createdBy);
    }

    #[Test]
    public function to_domain_works_with_different_email_formats(): void
    {
        $emailTestCases = [
            'simple@example.com',
            'user.name@example.com',
            'user+tag@example.com',
            'user@subdomain.example.com',
            'user@example.co.uk',
            'USER@EXAMPLE.COM', // uppercase
            '123@example.com', // numbers
            'user.name+tag@subdomain.example.co.uk', // complex
        ];

        foreach ($emailTestCases as $email) {
            $data = [
                'studentId' => 1,
                'parentEmail' => $email,
                'createdBy' => 1,
            ];

            $result = ParentInviteMapper::toDomain($data);
            $this->assertEquals($email, $result->email, "Failed for email: {$email}");
        }
    }

    #[Test]
    public function to_domain_creates_fresh_instance_each_time(): void
    {
        // Arrange
        $data = [
            'studentId' => 1,
            'parentEmail' => 'test@example.com',
            'createdBy' => 1,
        ];

        // Act - Crear múltiples instancias
        $instance1 = ParentInviteMapper::toDomain($data);
        $instance2 = ParentInviteMapper::toDomain($data);

        // Assert - Deben ser instancias diferentes
        $this->assertNotSame($instance1, $instance2);
        $this->assertNotEquals($instance1->token, $instance2->token); // Tokens diferentes
    }

}
