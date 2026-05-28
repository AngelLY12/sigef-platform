<?php

namespace Tests\Unit\Application\UseCases\Integration\Parents;

use App\Core\Application\UseCases\Parents\AcceptParentInvitationUseCase;
use App\Core\Domain\Enum\User\RelationshipType;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Repositories\Command\Misc\ParentInviteRepInterface;
use App\Core\Domain\Repositories\Query\Misc\ParentInviteQueryRepInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\NotAllowed\InvalidInvitationException;
use App\Exceptions\NotFound\UserNotFoundException;
use App\Models\ParentInvite;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcceptParentInvitationUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        Event::fake();
    }

    #[Test]
    public function it_accepts_parent_invitation_successfully(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('parent@example.com')
            ->active()
            ->create();

        $useCase = app(AcceptParentInvitationUseCase::class);

        // Act
        $useCase->execute($invite->token);

        // Assert
        // Verificar que la invitación fue marcada como usada
        $invite->refresh();
        $this->assertNotNull($invite->used_at);

        // Verificar que se creó la relación parent-student
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relationship' => null
        ]);

        // Verificar eventos
        Event::assertDispatched(\App\Events\ParentInvitationAccepted::class);
        Event::assertNotDispatched(\App\Events\ParentInvitationFailed::class);
    }

    #[Test]
    public function it_accepts_parent_invitation_with_relationship(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('parent@example.com')
            ->active()
            ->create();

        $useCase = app(AcceptParentInvitationUseCase::class);
        $relationship = RelationshipType::PADRE->value;

        // Act
        $useCase->execute($invite->token, $relationship);

        // Assert
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relationship' => $relationship
        ]);

        Event::assertDispatched(\App\Events\ParentInvitationAccepted::class);
    }

    #[Test]
    public function it_assigns_parent_role_if_not_already_parent(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create(['email' => 'parent@example.com']);
        // No asignar rol PARENT

        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('parent@example.com')
            ->active()
            ->create();

        $useCase = app(AcceptParentInvitationUseCase::class);

        // Act
        $useCase->execute($invite->token);

        // Assert - Debería asignar rol PARENT
        $parent->refresh();
        $this->assertTrue($parent->hasRole(UserRoles::PARENT->value));

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id
        ]);

        Event::assertDispatched(\App\Events\ParentInvitationAccepted::class);
    }

    #[Test]
    public function it_throws_exception_for_invalid_token(): void
    {
        // Arrange
        $invalidToken = 'invalid-token-12345';
        $useCase = app(AcceptParentInvitationUseCase::class);

        // Assert
        $this->expectException(InvalidInvitationException::class);

        // Act
        $useCase->execute($invalidToken);
    }

    #[Test]
    public function it_throws_exception_for_already_used_invitation(): void
    {
        // Arrange
        $invite = ParentInvite::factory()->used()->create();

        $useCase = app(AcceptParentInvitationUseCase::class);

        // Assert
        $this->expectException(InvalidInvitationException::class);

        // Act
        $useCase->execute($invite->token);
    }

    #[Test]
    public function it_throws_exception_for_expired_invitation(): void
    {
        // Arrange
        $invite = ParentInvite::factory()->expired()->create();

        $useCase = app(AcceptParentInvitationUseCase::class);

        // Assert
        $this->expectException(InvalidInvitationException::class);

        // Act
        $useCase->execute($invite->token);
    }


    #[Test]
    public function it_throws_exception_when_parent_not_found_by_email(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('nonexistent@example.com')
            ->active()
            ->create();

        $useCase = app(AcceptParentInvitationUseCase::class);

        // Assert
        $this->expectException(UserNotFoundException::class);

        // Act
        $useCase->execute($invite->token);
    }

    #[Test]
    public function it_rolls_back_transaction_on_exception(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('parent@example.com')
            ->active()
            ->create();

        // Mock para simular error en la creación de parent-student
        $mockParentRepo = $this->mock(\App\Core\Domain\Repositories\Command\User\ParentStudentRepInterface::class);
        $mockParentRepo->shouldReceive('create')
            ->andThrow(new \Exception('Database error'));

        $useCase = new AcceptParentInvitationUseCase(
            app(ParentInviteQueryRepInterface::class),
            app(ParentInviteRepInterface::class),
            $mockParentRepo,
            app(\App\Core\Domain\Repositories\Query\User\UserQueryRepInterface::class),
            app(\App\Core\Domain\Repositories\Command\User\UserRepInterface::class),
            app(CacheService::class)
        );

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        // Act
        $useCase->execute($invite->token);

        // Assert - Verificar rollback
        // La invitación no debería estar marcada como usada
        $invite->refresh();
        $this->assertNull($invite->used_at);

        // No debería existir relación parent-student
        $this->assertDatabaseMissing('parent_students', [
            'parent_id' => $parent->id,
            'student_id' => $student->id
        ]);

        // Debería disparar evento de fallo
        Event::assertDispatched(\App\Events\ParentInvitationFailed::class);
        Event::assertNotDispatched(\App\Events\ParentInvitationAccepted::class);
    }

    #[Test]
    public function it_clears_cache_tags_successfully(): void
    {
        $student = User::factory()->asStudent()->create();
        $studentId = $student->id;
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentId = $parent->id;
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);
        // Arrange
        Cache::shouldReceive('tags')
            ->with(['parent', 'children', "parent:{$parentId}"])
            ->once()
            ->andReturnSelf();

        Cache::shouldReceive('flush')
            ->once()
            ->andReturnTrue();

        Cache::shouldReceive('tags')
            ->with(['student', 'parents', "student:{$studentId}"])
            ->once()
            ->andReturnSelf();

        Cache::shouldReceive('flush')
            ->once()
            ->andReturnTrue();


        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('parent@example.com')
            ->active()
            ->create();

        $useCase = app(AcceptParentInvitationUseCase::class);

        // Act
        $useCase->execute($invite->token);

        // Assert
        Event::assertDispatched(\App\Events\ParentInvitationAccepted::class);
    }

    #[Test]
    public function it_handles_student_without_student_role(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        // No asignar rol STUDENT

        $parent = User::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('parent@example.com')
            ->active()
            ->create();

        $useCase = app(AcceptParentInvitationUseCase::class);

        // Assert - Debería fallar porque el estudiante no tiene rol STUDENT
        $this->expectException(\Exception::class);

        // Act
        $useCase->execute($invite->token);
    }

    #[Test]
    public function it_creates_parent_student_relationship_only_once(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('parent@example.com')
            ->active()
            ->create();

        // Primera aceptación
        $useCase = app(AcceptParentInvitationUseCase::class);
        $useCase->execute($invite->token);

        // Intentar aceptar de nuevo (debería fallar porque ya está usada)
        $this->expectException(InvalidInvitationException::class);
        $useCase->execute($invite->token);

        // Assert - Solo una relación debería existir
        $count = DB::table('parent_students')
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->count();
        $this->assertEquals(1, $count);
    }

    #[Test]
    public function it_handles_about_to_expire_invitation(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('parent@example.com')
            ->aboutToExpire() // Usando el método del factory
            ->create();

        $useCase = app(AcceptParentInvitationUseCase::class);

        // Act
        $useCase->execute($invite->token);

        // Assert - Debería funcionar porque aún no ha expirado
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id
        ]);

        Event::assertDispatched(\App\Events\ParentInvitationAccepted::class);
    }

    #[Test]
    public function it_handles_recently_created_invitation(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        $invite = ParentInvite::factory()
            ->forStudent($student)
            ->withEmail('parent@example.com')
            ->recentlyCreated() // Usando el método del factory
            ->create();

        $useCase = app(AcceptParentInvitationUseCase::class);

        // Act
        $useCase->execute($invite->token);

        // Assert
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id
        ]);

        Event::assertDispatched(\App\Events\ParentInvitationAccepted::class);
    }

}
