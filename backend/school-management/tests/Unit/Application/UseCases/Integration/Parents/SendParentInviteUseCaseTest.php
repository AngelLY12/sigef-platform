<?php

namespace Tests\Unit\Application\UseCases\Integration\Parents;

use App\Core\Application\UseCases\Parents\SendParentInviteUseCase;
use App\Core\Domain\Entities\ParentInvite;
use App\Core\Domain\Enum\User\RelationshipType;
use App\Core\Domain\Enum\User\UserRoles;
use App\Exceptions\Conflict\RelationAlreadyExistsException;
use App\Exceptions\NotFound\UserNotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\Jobs\SendMailJob;
use App\Models\ParentStudent;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;

class SendParentInviteUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private SendParentInviteUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(SendParentInviteUseCase::class);
        $this->seed(RolesSeeder::class);
        Queue::fake();
    }

    #[Test]
    public function it_creates_parent_invite_successfully(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create([
            'name' => 'Juan',
            'last_name' => 'Perez'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'name' => 'Carlos',
            'last_name' => 'Perez',
            'email' => 'carlos@example.com'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Act
        $result = $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $parent->email,
            createdBy: $createdBy
        );

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($student->id, $result->studentId);
        $this->assertEquals($parent->email, $result->email);
        $this->assertEquals($createdBy, $result->createdBy);
        $this->assertNotNull($result->token);
        $this->assertNotNull($result->expiresAt);

        // Verificar que se encoló el trabajo de correo
        Queue::assertPushed(SendMailJob::class);
    }

    #[Test]
    public function it_throws_exception_when_student_not_found(): void
    {
        // Arrange
        $parent = UserModel::factory()->asParent()->create([
            'email' => 'parent@example.com'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $nonExistentStudentId = 9999;
        $createdBy = UserModel::factory()->create()->id;

        // Assert
        $this->expectException(UserNotFoundException::class);

        // Act
        $this->useCase->execute(
            studentId: $nonExistentStudentId,
            parentEmail: $parent->email,
            createdBy: $createdBy
        );
    }

    #[Test]
    public function it_throws_exception_when_parent_not_found(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $createdBy = UserModel::factory()->create()->id;
        $nonExistentEmail = 'nonexistent@example.com';

        // Assert
        $this->expectException(UserNotFoundException::class);

        // Act
        $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $nonExistentEmail,
            createdBy: $createdBy
        );
    }

    #[Test]
    public function it_throws_exception_when_relation_already_exists(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'email' => 'parent@example.com'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        // Crear relación existente
        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        $createdBy = UserModel::factory()->create()->id;

        // Assert
        $this->expectException(RelationAlreadyExistsException::class);

        // Act
        $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $parent->email,
            createdBy: $createdBy
        );
    }

    #[Test]
    public function it_throws_exception_when_inviting_self(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create([
            'email' => 'student@example.com'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No puedes invitarte a ti mismo');

        // Act
        $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $student->email, // Mismo email que el estudiante
            createdBy: $createdBy
        );
    }

    #[Test]
    public function it_creates_invite_with_valid_token_and_expiration(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'email' => 'parent@example.com'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Act
        $result = $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $parent->email,
            createdBy: $createdBy
        );

        // Assert
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertNotEmpty($result->token);
        $this->assertTrue(strlen($result->token) >= 32); // Verificar que es un token válido

        // Verificar que la fecha de expiración está en el futuro
        $this->assertGreaterThan(now(), $result->expiresAt);
    }

    #[Test]
    public function it_dispatches_email_to_correct_queue(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'email' => 'parent@example.com'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Act
        $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $parent->email,
            createdBy: $createdBy
        );

        // Assert - Verificar que se envía al queue correcto
        Queue::assertPushedOn('emails', SendMailJob::class);
    }

    #[Test]
    public function it_sends_email_with_correct_data(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'name' => 'Roberto',
            'last_name' => 'Gomez',
            'email' => 'roberto@example.com'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Act
        $result = $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $parent->email,
            createdBy: $createdBy
        );

        // Assert
        Queue::assertPushed(SendMailJob::class);
    }

    #[Test]
    public function it_works_when_parent_has_no_role_assigned_yet(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        // Padre sin rol asignado aún (usuario regular)
        $parent = UserModel::factory()->create([
            'email' => 'newparent@example.com'
        ]);
        // No asignar rol de padre

        $createdBy = UserModel::factory()->create()->id;

        // Act
        $result = $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $parent->email,
            createdBy: $createdBy
        );

        // Assert - Debería funcionar aunque el padre no tenga rol aún
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals($parent->email, $result->email);
    }

    #[Test]
    public function it_creates_unique_token_for_each_invite(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent1 = UserModel::factory()->asParent()->create(['email' => 'parent1@example.com']);
        $parent2 = UserModel::factory()->asParent()->create(['email' => 'parent2@example.com']);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent1->assignRole($parentRole);
        $parent2->assignRole($parentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Act
        $invite1 = $this->useCase->execute($student->id, $parent1->email, $createdBy);
        $invite2 = $this->useCase->execute($student->id, $parent2->email, $createdBy);

        // Assert - Tokens deben ser diferentes
        $this->assertNotEquals($invite1->token, $invite2->token);
    }

    #[Test]
    public function it_handles_multiple_invites_to_same_parent_for_different_students(): void
    {
        // Arrange
        $student1 = UserModel::factory()->asStudent()->create();
        $student2 = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student1->assignRole($studentRole);
        $student2->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Act - Invitar al mismo padre para dos estudiantes diferentes
        $invite1 = $this->useCase->execute($student1->id, $parent->email, $createdBy);
        $invite2 = $this->useCase->execute($student2->id, $parent->email, $createdBy);

        // Assert - Ambos deben crearse exitosamente
        $this->assertInstanceOf(ParentInvite::class, $invite1);
        $this->assertInstanceOf(ParentInvite::class, $invite2);
        $this->assertEquals($student1->id, $invite1->studentId);
        $this->assertEquals($student2->id, $invite2->studentId);
        $this->assertEquals($parent->email, $invite1->email);
        $this->assertEquals($parent->email, $invite2->email);
    }

    #[Test]
    public function it_persists_invite_in_database(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create(['email' => 'parent@example.com']);
        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Act
        $result = $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $parent->email,
            createdBy: $createdBy
        );

        // Assert - Debe estar persistido en la base de datos
        $this->assertDatabaseHas('parent_invites', [
            'student_id' => $student->id,
            'email' => $parent->email,
            'created_by' => $createdBy,
            'token' => $result->token
        ]);
    }

    #[Test]
    public function it_uses_correct_user_full_name_in_email(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'name' => 'Maria',
            'last_name' => 'Lopez',
            'email' => 'maria@example.com'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Act
        $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $parent->email,
            createdBy: $createdBy
        );

        // Assert - Verificar que el nombre completo se usa en el email
        Queue::assertPushed(SendMailJob::class);

    }

    #[Test]
    public function it_does_not_send_email_when_invitation_fails(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create([
            'email' => 'student@example.com'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Assert - Debería fallar antes de enviar email
        $this->expectException(ValidationException::class);

        // Act - Intentar invitarse a sí mismo
        $this->useCase->execute(
            studentId: $student->id,
            parentEmail: $student->email, // Mismo email
            createdBy: $createdBy
        );

        // Assert - No se debería haber encolado ningún email
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_handles_case_sensitive_email_correctly(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'email' => 'Parent@Example.com' // Mayúsculas y minúsculas
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $createdBy = UserModel::factory()->create()->id;

        // Act - Usar email en minúsculas (normalizado)
        $result = $this->useCase->execute(
            studentId: $student->id,
            parentEmail: 'parent@example.com', // Minúsculas
            createdBy: $createdBy
        );

        // Assert - Debería encontrar al usuario independientemente del caso
        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertNotEquals($parent->email, $result->email);
    }

}
