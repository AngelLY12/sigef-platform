<?php

namespace Tests\Unit\Infraestructure\Mappers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\ParentInvite;
use App\Core\Domain\Entities\ParentInvite as DomainParentInvite;
use App\Core\Infraestructure\Mappers\ParentInviteMapper;
use Carbon\Carbon;

class ParentInviteMapperTest extends TestCase
{
    #[Test]
    public function it_maps_from_eloquent_to_domain_correctly(): void
    {
        // Arrange
        $eloquentModel = new ParentInvite();
        $eloquentModel->id = 1;
        $eloquentModel->student_id = 100;
        $eloquentModel->email = 'parent@example.com';
        $eloquentModel->token = 'abc123token';
        $eloquentModel->expires_at = Carbon::parse('2024-12-31 23:59:59');
        $eloquentModel->created_by = 50;
        $eloquentModel->used_at = Carbon::parse('2024-01-15 10:30:00');

        // Act
        $domainEntity = ParentInviteMapper::toDomain($eloquentModel);

        // Assert
        $this->assertInstanceOf(DomainParentInvite::class, $domainEntity);
        $this->assertEquals(1, $domainEntity->id);
        $this->assertEquals(100, $domainEntity->studentId);
        $this->assertEquals('parent@example.com', $domainEntity->email);
        $this->assertEquals('abc123token', $domainEntity->token);
        $this->assertEquals('2024-12-31 23:59:59', $domainEntity->expiresAt);
        $this->assertEquals(50, $domainEntity->createdBy);
        $this->assertEquals('2024-01-15 10:30:00', $domainEntity->usedAt);
    }

    #[Test]
    public function it_maps_from_domain_to_persistence_array_correctly(): void
    {
        // Arrange
        $domainEntity = new DomainParentInvite(
            studentId: 100,
            email: 'parent@example.com',
            token: 'abc123token',
            expiresAt: new Carbon('2024-12-31 23:59:59'),
            createdBy: 50,
            id: 1,
            usedAt: new Carbon('2024-01-15 10:30:00')
        );

        // Act
        $persistenceArray = ParentInviteMapper::toPersistence($domainEntity);

        // Assert
        $this->assertIsArray($persistenceArray);
        $this->assertEquals([
            'student_id' => 100,
            'email' => 'parent@example.com',
            'token' => 'abc123token',
            'expires_at' => '2024-12-31 23:59:59',
            'created_by' => 50,
        ], $persistenceArray);

        // Verify that id and usedAt are NOT included in persistence array
        $this->assertArrayNotHasKey('id', $persistenceArray);
        $this->assertArrayNotHasKey('used_at', $persistenceArray);
    }

    #[Test]
    public function it_handles_null_values_correctly_in_to_domain(): void
    {
        // Arrange
        $eloquentModel = new ParentInvite([
            'student_id' => 100,
            'email' => 'parent@example.com',
            'token' => 'abc123token',
            'expires_at' => Carbon::now()->addDay(),
            'created_by' => 50,
            'id' => null,
            'used_at' => null,
        ]);

        // Act
        $domainEntity = ParentInviteMapper::toDomain($eloquentModel);

        // Assert
        $this->assertNull($domainEntity->id);
        $this->assertNull($domainEntity->usedAt);
        $this->assertEquals(100, $domainEntity->studentId);
        $this->assertEquals('parent@example.com', $domainEntity->email);
    }

    #[Test]
    public function it_handles_null_values_correctly_in_to_persistence(): void
    {
        // Arrange
        $expiresAtDate= Carbon::now()->addDay();
        $domainEntity = new DomainParentInvite(
            studentId: 100,
            email: 'parent@example.com',
            token: 'abc123token',
            expiresAt: $expiresAtDate,
            createdBy: 50,
            id: null,
            usedAt: null
        );

        // Act
        $persistenceArray = ParentInviteMapper::toPersistence($domainEntity);

        // Assert
        $this->assertArrayHasKey('expires_at', $persistenceArray);
        $this->assertEquals($expiresAtDate,$persistenceArray['expires_at']);
    }

}
