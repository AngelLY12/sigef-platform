<?php

namespace Tests\Unit\Infraestructure\Repositories\Query;

use App\Core\Domain\Entities\ParentInvite;
use App\Core\Infraestructure\Repositories\Query\Misc\EloquentParentInviteQueryRepository;
use App\Models\ParentInvite as EloquentParentInvite;
use App\Models\User as EloquentUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentParentInviteQueryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentParentInviteQueryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentParentInviteQueryRepository();
    }

    // ==================== FIND BY TOKEN TESTS ====================

    #[Test]
    public function find_by_token_successfully(): void
    {
        // Arrange
        $token = Str::uuid()->toString();
        $parentInvite = EloquentParentInvite::factory()
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($parentInvite->id, $result->id);
        $this->assertEquals($parentInvite->student_id, $result->studentId);
        $this->assertEquals($parentInvite->email, $result->email);
        $this->assertEquals($token, $result->token);
        $this->assertEquals(
            $parentInvite->expires_at->timestamp,
            $result->expiresAt->timestamp
        );
        $this->assertEquals($parentInvite->created_by, $result->createdBy);
        $this->assertNull($result->usedAt);
    }

    #[Test]
    public function find_by_token_with_used_invite(): void
    {
        // Arrange
        $token = Str::uuid()->toString();
        $parentInvite = EloquentParentInvite::factory()
            ->used()
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertNotNull($result->usedAt);
        $this->assertTrue($result->isUsed());
    }

    #[Test]
    public function find_by_token_with_expired_invite(): void
    {
        // Arrange
        $token = Str::uuid()->toString();
        EloquentParentInvite::factory()
            ->expired()
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertTrue($result->isExpired());
    }

    #[Test]
    public function find_by_token_with_active_invite(): void
    {
        // Arrange
        $token = Str::uuid()->toString();
        EloquentParentInvite::factory()
            ->active()
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertFalse($result->isUsed());
        $this->assertFalse($result->isExpired());
    }

    #[Test]
    public function find_by_token_with_about_to_expire_invite(): void
    {
        // Arrange
        $token = Str::uuid()->toString();
        EloquentParentInvite::factory()
            ->aboutToExpire()
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertFalse($result->isUsed());
        $this->assertFalse($result->isExpired()); // Aún no expira
    }

    #[Test]
    public function find_by_token_returns_null_for_nonexistent_token(): void
    {
        // Arrange
        $nonexistentToken = Str::uuid()->toString();

        // Act
        $result = $this->repository->findByToken($nonexistentToken);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_token_returns_null_for_empty_token(): void
    {
        // Act
        $result = $this->repository->findByToken('');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_token_with_uuid_format(): void
    {
        // Arrange
        $uuidToken = '550e8400-e29b-41d4-a716-446655440000';
        $parentInvite = EloquentParentInvite::factory()
            ->withToken($uuidToken)
            ->create();

        // Act
        $result = $this->repository->findByToken($uuidToken);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($uuidToken, $result->token);
    }

    // ==================== SPECIFIC STUDENT/CREATOR TESTS ====================

    #[Test]
    public function find_by_token_for_specific_student(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create();
        $token = Str::uuid()->toString();

        $parentInvite = EloquentParentInvite::factory()
            ->forStudent($student)
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($student->id, $result->studentId);
    }

    #[Test]
    public function find_by_token_created_by_specific_user(): void
    {
        // Arrange
        $creator = EloquentUser::factory()->create();
        $token = Str::uuid()->toString();

        $parentInvite = EloquentParentInvite::factory()
            ->createdBy($creator)
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($creator->id, $result->createdBy);
    }

    #[Test]
    public function find_by_token_with_specific_email(): void
    {
        // Arrange
        $email = 'specific.parent@example.com';
        $token = Str::uuid()->toString();

        $parentInvite = EloquentParentInvite::factory()
            ->withEmail($email)
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($email, $result->email);
    }

    // ==================== MULTIPLE INVITES TESTS ====================

    #[Test]
    public function find_by_token_with_multiple_invites_exists(): void
    {
        // Arrange - Crear múltiples invites
        $student = EloquentUser::factory()->create();
        $creator = EloquentUser::factory()->create();

        $tokens = [
            Str::uuid()->toString(),
            Str::uuid()->toString(),
            Str::uuid()->toString()
        ];

        // Crear invites con diferentes estados
        EloquentParentInvite::factory()
            ->forStudent($student)
            ->createdBy($creator)
            ->active()
            ->withToken($tokens[0])
            ->create();

        EloquentParentInvite::factory()
            ->forStudent($student)
            ->createdBy($creator)
            ->expired()
            ->withToken($tokens[1])
            ->create();

        $targetInvite = EloquentParentInvite::factory()
            ->forStudent($student)
            ->createdBy($creator)
            ->used()
            ->withToken($tokens[2])
            ->create();

        // Act - Buscar el tercer invite
        $result = $this->repository->findByToken($tokens[2]);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($targetInvite->id, $result->id);
        $this->assertTrue($result->isUsed());
    }

    #[Test]
    public function find_by_token_returns_correct_invite_among_many(): void
    {
        // Arrange - Crear 20 invites
        $targetToken = Str::uuid()->toString();
        $targetStudent = EloquentUser::factory()->create();

        // Crear 19 invites aleatorios
        EloquentParentInvite::factory()->count(19)->create();

        // Crear invite objetivo
        $targetInvite = EloquentParentInvite::factory()
            ->forStudent($targetStudent)
            ->withToken($targetToken)
            ->create();

        // Act
        $result = $this->repository->findByToken($targetToken);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($targetStudent->id, $result->studentId);
        $this->assertEquals($targetInvite->id, $result->id);
    }

    // ==================== DOMAIN OBJECT TESTS ====================

    #[Test]
    public function parent_invite_domain_object_validation_methods(): void
    {
        // Arrange
        $activeToken = Str::uuid()->toString();
        $expiredToken = Str::uuid()->toString();
        $usedToken = Str::uuid()->toString();

        EloquentParentInvite::factory()
            ->active()
            ->withToken($activeToken)
            ->create();

        EloquentParentInvite::factory()
            ->expired()
            ->withToken($expiredToken)
            ->create();

        EloquentParentInvite::factory()
            ->used()
            ->withToken($usedToken)
            ->create();

        // Act & Assert
        $activeInvite = $this->repository->findByToken($activeToken);
        $this->assertFalse($activeInvite->isUsed());
        $this->assertFalse($activeInvite->isExpired());

        $expiredInvite = $this->repository->findByToken($expiredToken);
        $this->assertFalse($expiredInvite->isUsed());
        $this->assertTrue($expiredInvite->isExpired());

        $usedInvite = $this->repository->findByToken($usedToken);
        $this->assertTrue($usedInvite->isUsed());
        $this->assertTrue($usedInvite->isExpired()); // Usado y expirado
    }

    #[Test]
    public function parent_invite_domain_object_properties(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create();
        $creator = EloquentUser::factory()->create();
        $email = 'test.parent@university.edu';
        $token = 'test-token-123';
        $expiresAt = Carbon::now()->addDays(5);

        $invite = EloquentParentInvite::factory()
            ->forStudent($student)
            ->createdBy($creator)
            ->withEmail($email)
            ->withToken($token)
            ->create([
                'expires_at' => $expiresAt
            ]);

        // Act
        $result = $this->repository->findByToken($token);

        // Assert - Verificar todas las propiedades
        $this->assertNotNull($result);
        $this->assertEquals($invite->id, $result->id);
        $this->assertEquals($student->id, $result->studentId);
        $this->assertEquals($email, $result->email);
        $this->assertEquals($token, $result->token);
        $this->assertEquals($expiresAt->timestamp, $result->expiresAt->timestamp);
        $this->assertEquals($creator->id, $result->createdBy);
        $this->assertNull($result->usedAt);
    }

    // ==================== EDGE CASES TESTS ====================

    #[Test]
    public function find_by_token_after_invite_deleted(): void
    {
        // Arrange
        $token = Str::uuid()->toString();
        $invite = EloquentParentInvite::factory()
            ->withToken($token)
            ->create();

        // Act 1 - Buscar antes de eliminar
        $result1 = $this->repository->findByToken($token);
        $this->assertNotNull($result1);

        // Eliminar el invite
        $invite->delete();

        // Act 2 - Buscar después de eliminar
        $result2 = $this->repository->findByToken($token);

        // Assert
        $this->assertNull($result2);
    }

    #[Test]
    public function find_by_token_with_recently_created_invite(): void
    {
        // Arrange
        $token = Str::uuid()->toString();
        $invite = EloquentParentInvite::factory()
            ->recentlyCreated()
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        // Verificar que fue creado recientemente (última semana)
        $createdAgo = now()->diffInDays($invite->created_at);
        $this->assertLessThanOrEqual(7, $createdAgo);
    }

    #[Test]
    public function find_by_token_with_parent_email_factory(): void
    {
        // Arrange
        $token = Str::uuid()->toString();
        EloquentParentInvite::factory()
            ->forParentEmail()
            ->withToken($token)
            ->create();

        // Act
        $result = $this->repository->findByToken($token);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        // El email debería ser válido (factory usa safeEmail)
        $this->assertStringContainsString('@', $result->email);
    }

    // ==================== PERFORMANCE TESTS ====================

    #[Test]
    public function find_by_token_performance_with_many_records(): void
    {
        // Arrange
        $targetToken = Str::uuid()->toString();
        $targetStudent = EloquentUser::factory()->create();

        // Crear muchos invites (100 es suficiente para prueba de rendimiento)
        for ($i = 0; $i < 100; $i++) {
            EloquentParentInvite::factory()
                ->withToken(($i == 50) ? $targetToken : Str::uuid()->toString())
                ->forStudent(($i == 50) ? $targetStudent : EloquentUser::factory()->create())
                ->create();
        }

        // Act
        $startTime = microtime(true);
        $result = $this->repository->findByToken($targetToken);
        $endTime = microtime(true);

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($targetStudent->id, $result->studentId);

        // Verificar rendimiento (debería ser rápido)
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(0.05, $executionTime,
            "La búsqueda por token debería ser rápida (< 50ms), tomó: " . ($executionTime * 1000) . "ms"
        );
    }

    // ==================== INTEGRATION TESTS ====================

    #[Test]
    public function complete_parent_invite_lifecycle(): void
    {
        // 1. Verificar que no hay invites inicialmente
        $randomToken = Str::uuid()->toString();
        $initialResult = $this->repository->findByToken($randomToken);
        $this->assertNull($initialResult);

        // 2. Crear usuarios
        $student = EloquentUser::factory()->create();
        $admin = EloquentUser::factory()->create();

        // 3. Crear invite usando factory methods
        $token = 'integration-test-token';
        $email = 'integration.parent@example.com';

        $invite = EloquentParentInvite::factory()
            ->forStudent($student)
            ->createdBy($admin)
            ->withEmail($email)
            ->withToken($token)
            ->active()
            ->create();

        // 4. Buscar invite por token
        $foundInvite = $this->repository->findByToken($token);
        $this->assertInstanceOf(ParentInvite::class, $foundInvite);
        $this->assertEquals($student->id, $foundInvite->studentId);
        $this->assertEquals($email, $foundInvite->email);
        $this->assertFalse($foundInvite->isUsed());
        $this->assertFalse($foundInvite->isExpired());

        // 5. Marcar como usado
        $invite->update(['used_at' => now()]);
        $invite->refresh();

        // 6. Buscar nuevamente
        $usedInvite = $this->repository->findByToken($token);
        $this->assertNotNull($usedInvite->usedAt);
        $this->assertTrue($usedInvite->isUsed());

        // 7. Buscar token inexistente
        $nonexistent = $this->repository->findByToken('nonexistent-token-123');
        $this->assertNull($nonexistent);
    }

    #[Test]
    public function multiple_invites_for_same_student(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create();
        $tokens = [
            Str::uuid()->toString(),
            Str::uuid()->toString(),
            Str::uuid()->toString()
        ];

        // Crear múltiples invites para el mismo estudiante
        foreach ($tokens as $token) {
            EloquentParentInvite::factory()
                ->forStudent($student)
                ->withToken($token)
                ->create();
        }

        // Act & Assert - Buscar cada uno por su token
        foreach ($tokens as $token) {
            $result = $this->repository->findByToken($token);
            $this->assertInstanceOf(ParentInvite::class, $result);
            $this->assertEquals($student->id, $result->studentId);
        }
    }

    #[Test]
    public function repository_independent_of_other_fields(): void
    {
        // Este test verifica que el repositorio solo busca por token
        // y no se ve afectado por otros campos

        $student1 = EloquentUser::factory()->create();
        $student2 = EloquentUser::factory()->create();
        $admin = EloquentUser::factory()->create();

        // Crear invites con mismo email pero diferentes tokens
        $token1 = 'token-for-student1';
        $token2 = 'token-for-student2';

        EloquentParentInvite::factory()
            ->forStudent($student1)
            ->createdBy($admin)
            ->withEmail('same.parent@example.com')
            ->withToken($token1)
            ->create();

        EloquentParentInvite::factory()
            ->forStudent($student2)
            ->createdBy($admin)
            ->withEmail('same.parent@example.com')
            ->withToken($token2)
            ->create();

        // Act - Buscar cada uno por su token
        $result1 = $this->repository->findByToken($token1);
        $result2 = $this->repository->findByToken($token2);

        // Assert
        $this->assertNotNull($result1);
        $this->assertEquals($student1->id, $result1->studentId);

        $this->assertNotNull($result2);
        $this->assertEquals($student2->id, $result2->studentId);

        $this->assertNotEquals($result1->id, $result2->id);
    }

    #[Test]
    public function factory_multiple_for_student_method(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create();
        $creator = EloquentUser::factory()->create();

        // Usar el método de la factory para crear múltiples invites
        $invites = [];
        for ($i = 0; $i < 3; $i++) {
            $invites[] = EloquentParentInvite::factory()
                ->forStudent($student)
                ->createdBy($creator)
                ->withToken(Str::uuid()->toString())
                ->create();
        }

        // Act & Assert - Buscar cada invite
        foreach ($invites as $invite) {
            $result = $this->repository->findByToken($invite->token);
            $this->assertInstanceOf(ParentInvite::class, $result);
            $this->assertEquals($student->id, $result->studentId);
            $this->assertEquals($creator->id, $result->createdBy);
        }
    }

}
