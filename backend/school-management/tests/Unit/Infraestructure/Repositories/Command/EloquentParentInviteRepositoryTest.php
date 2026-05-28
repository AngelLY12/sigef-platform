<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Infraestructure\Repositories\Command\Misc\EloquentParentInviteRepository;
use App\Models\ParentInvite;
use App\Core\Domain\Entities\ParentInvite as DomainParentInvite;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentParentInviteRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private EloquentParentInviteRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentParentInviteRepository();

        // Limpiar antes de cada test
        ParentInvite::query()->delete();
    }

    #[Test]
    public function create_invite_saves_to_database_and_returns_domain_entity(): void
    {
        // Arrange
        $student = User::factory()->create();
        $creator = User::factory()->create();

        $domainInvite = new DomainParentInvite(
            studentId: $student->id,
            email: 'parent@example.com',
            token: Str::uuid()->toString(),
            expiresAt: Carbon::now()->addDays(7),
            createdBy: $creator->id
        );

        // Act
        $result = $this->repository->create($domainInvite);

        // Assert
        $this->assertInstanceOf(DomainParentInvite::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals($student->id, $result->studentId);
        $this->assertEquals('parent@example.com', $result->email);
        $this->assertNull($result->usedAt);

        $this->assertDatabaseHas('parent_invites', [
            'student_id' => $student->id,
            'email' => 'parent@example.com',
            'used_at' => null
        ]);
    }

    #[Test]
    public function markAsUsed_updates_invite_timestamp(): void
    {
        // Arrange - Usando factory
        $invite = ParentInvite::factory()->create([
            'used_at' => null,
            'expires_at' => Carbon::now()->addDays(7)
        ]);

        // Act
        $result = $this->repository->markAsUsed($invite->id);

        // Assert
        $this->assertTrue($result);

        $invite->refresh();
        $this->assertNotNull($invite->used_at);
        $this->assertTrue(Carbon::parse($invite->used_at)->isToday());
    }

    #[Test]
    public function markAsUsed_returns_false_when_invite_not_found(): void
    {
        // Arrange
        $nonExistentId = 999999;

        // Act
        $result = $this->repository->markAsUsed($nonExistentId);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function deleteExpired_removes_only_expired_invites(): void
    {
        // Arrange - Usando factory con estados específicos
        $now = Carbon::now();

        // Expired invites usando factory
        $expired1 = ParentInvite::factory()->expired()->create();
        $expired2 = ParentInvite::factory()->expired()->create();

        // Active invites usando factory
        $active1 = ParentInvite::factory()->active()->create();
        $active2 = ParentInvite::factory()->active()->create();

        // Act
        $deletedCount = $this->repository->deleteExpired();

        // Assert
        $this->assertEquals(2, $deletedCount);

        $this->assertDatabaseMissing('parent_invites', ['id' => $expired1->id]);
        $this->assertDatabaseMissing('parent_invites', ['id' => $expired2->id]);
        $this->assertDatabaseHas('parent_invites', ['id' => $active1->id]);
        $this->assertDatabaseHas('parent_invites', ['id' => $active2->id]);
    }

    #[Test]
    public function deleteExpired_returns_zero_when_no_expired_invites(): void
    {
        // Arrange - Solo invites activos usando factory
        ParentInvite::factory()->active()->count(3)->create();

        // Act
        $deletedCount = $this->repository->deleteExpired();

        // Assert
        $this->assertEquals(0, $deletedCount);
        $this->assertDatabaseCount('parent_invites', 3);
    }

    #[Test]
    public function deleteExpired_with_mixed_invites(): void
    {
        // Arrange
        ParentInvite::factory()->expired()->count(2)->create();
        ParentInvite::factory()->active()->count(3)->create();
        ParentInvite::factory()->used()->count(1)->create(); // Usado pero no expirado

        // Act
        $deletedCount = $this->repository->deleteExpired();

        // Assert - Solo los expirados (no importa si están usados o no)
        $this->assertEquals(3, $deletedCount);
        $this->assertDatabaseCount('parent_invites', 3); // 3 activos
    }

    #[Test]
    public function create_invite_with_factory_pattern_data(): void
    {
        // Arrange
        $student = User::factory()->create();
        $creator = User::factory()->create();

        // Datos que podría generar el factory
        $factoryLikeData = ParentInvite::factory()->make();

        $domainInvite = new DomainParentInvite(
            studentId: $student->id,
            email: $factoryLikeData->email,
            token: $factoryLikeData->token,
            expiresAt: $factoryLikeData->expires_at,
            createdBy: $creator->id
        );

        // Act
        $result = $this->repository->create($domainInvite);

        // Assert
        $this->assertEquals($factoryLikeData->email, $result->email);
        $this->assertEquals($factoryLikeData->token, $result->token);
        $this->assertDatabaseHas('parent_invites', [
            'email' => $factoryLikeData->email,
            'token' => $factoryLikeData->token
        ]);
    }

    #[Test]
    public function markAsUsed_on_factory_created_invites(): void
    {
        // Arrange - Crear varios invites con factory
        $invites = ParentInvite::factory()
            ->count(3)
            ->state(['used_at' => null])
            ->create();

        // Act - Marcar algunos como usados
        $result1 = $this->repository->markAsUsed($invites[0]->id);
        $result2 = $this->repository->markAsUsed($invites[1]->id);

        // Assert
        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Verificar estado actualizado
        $invites[0]->refresh();
        $invites[1]->refresh();
        $invites[2]->refresh();

        $this->assertNotNull($invites[0]->used_at);
        $this->assertNotNull($invites[1]->used_at);
        $this->assertNull($invites[2]->used_at);
    }

    #[Test]
    public function bulk_operations_with_factory_data(): void
    {
        // Arrange
        // Crear invites en diferentes estados usando factory
        $expiredInvites = ParentInvite::factory()->expired()->count(5)->create();
        $activeInvites = ParentInvite::factory()->active()->count(3)->create();
        $usedInvites = ParentInvite::factory()->used()->count(2)->create();

        // Act 1 - Eliminar expirados
        $deletedCount = $this->repository->deleteExpired();

        // Assert 1
        $this->assertEquals(7, $deletedCount);
        $this->assertDatabaseCount('parent_invites', 3); // 3 activos

        // Act 2 - Marcar algunos como usados
        $markResult1 = $this->repository->markAsUsed($activeInvites[0]->id);
        $markResult2 = $this->repository->markAsUsed($activeInvites[1]->id);

        // Assert 2
        $this->assertTrue($markResult1);
        $this->assertTrue($markResult2);

        $activeInvites[0]->refresh();
        $activeInvites[1]->refresh();

        $this->assertNotNull($activeInvites[0]->used_at);
        $this->assertNotNull($activeInvites[1]->used_at);
        $this->assertNull($activeInvites[2]->used_at);
    }


}
