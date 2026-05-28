<?php

namespace Tests\Unit\Domain\Validators;

use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Utils\Validators\UserValidator;
use App\Exceptions\Conflict\UserAlreadyActiveException;
use App\Exceptions\Conflict\UserAlreadyDeletedException;
use App\Exceptions\Conflict\UserAlreadyDisabledException;
use App\Exceptions\Conflict\UserCannotBeDisabledException;
use App\Exceptions\Conflict\UserCannotBeUpdatedException;
use App\Exceptions\Conflict\UserConflictStatusException;
use App\Exceptions\Unauthorized\UserInactiveException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UserValidatorTest extends TestCase
{
    private function createMockUser(array $properties = []): MockObject
    {
        $mock = $this->createMock(User::class);

        $defaults = [
            'id' => 1,
            'status' => UserStatus::ACTIVO,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'curp' => 'TEST123456',
            'last_name' => 'Doe',
            'password' => 'password',
            'phone_number' => '1234567890',
        ];

        $properties = array_merge($defaults, $properties);

        // Configurar métodos
        $mock->method('isActive')->willReturn(
            $properties['status'] === UserStatus::ACTIVO
        );

        // Configurar propiedades públicas
        $mock->status = $properties['status'];
        $mock->id = $properties['id'];
        $mock->name = $properties['name'];
        $mock->email = $properties['email'];
        $mock->curp = $properties['curp'];
        $mock->last_name = $properties['last_name'];
        $mock->password = $properties['password'];
        $mock->phone_number = $properties['phone_number'];

        return $mock;
    }

    // Tests para ensureValidStatusTransition
    #[Test]
    public function ensureValidStatusTransition_passes_when_transition_allowed(): void
    {
        $user = $this->createMockUser([
            'status' => UserStatus::ACTIVO,
        ]);

        $this->expectNotToPerformAssertions();
        UserValidator::ensureValidStatusTransition($user, UserStatus::BAJA);
    }

    #[Test]
    public function ensureValidStatusTransition_throws_when_same_status_active(): void
    {
        $user = $this->createMockUser([
            'status' => UserStatus::ACTIVO,
        ]);

        $this->expectException(UserAlreadyActiveException::class);
        UserValidator::ensureValidStatusTransition($user, UserStatus::ACTIVO);
    }

    #[Test]
    public function ensureValidStatusTransition_throws_when_same_status_baja(): void
    {
        $user = $this->createMockUser([
            'status' => UserStatus::BAJA,
        ]);

        $this->expectException(UserAlreadyDisabledException::class);
        UserValidator::ensureValidStatusTransition($user, UserStatus::BAJA);
    }

    #[Test]
    public function ensureValidStatusTransition_throws_when_same_status_baja_temporal(): void
    {
        $user = $this->createMockUser([
            'status' => UserStatus::BAJA_TEMPORAL,
        ]);

        $this->expectException(UserAlreadyDisabledException::class);
        UserValidator::ensureValidStatusTransition($user, UserStatus::BAJA_TEMPORAL);
    }

    #[Test]
    public function ensureValidStatusTransition_throws_when_same_status_eliminado(): void
    {
        $user = $this->createMockUser([
            'status' => UserStatus::ELIMINADO,
        ]);

        $this->expectException(UserAlreadyDeletedException::class);
        UserValidator::ensureValidStatusTransition($user, UserStatus::ELIMINADO);
    }

    #[Test]
    public function ensureValidStatusTransition_throws_when_transition_not_allowed(): void
    {
        $invalidTransitions = [
            [UserStatus::BAJA, UserStatus::BAJA_TEMPORAL, UserConflictStatusException::class],
            [UserStatus::ELIMINADO, UserStatus::BAJA, UserCannotBeDisabledException::class], // Cambiado
            [UserStatus::ELIMINADO, UserStatus::BAJA_TEMPORAL, UserConflictStatusException::class],
        ];

        foreach ($invalidTransitions as [$currentStatus, $newStatus, $expectedException]) {
            $user = $this->createMockUser([
                'status' => $currentStatus,
            ]);

            $this->expectException($expectedException);
            UserValidator::ensureValidStatusTransition($user, $newStatus);
        }
    }

    #[Test]
    public function ensureValidStatusTransition_throws_user_cannot_be_disabled_for_specific_case(): void
    {
        // Según el validador, cuando newStatus es BAJA y no se puede hacer la transición
        // lanza UserCannotBeDisabledException
        // Ejemplo: ELIMINADO → BAJA no está permitido
        $user = $this->createMockUser([
            'status' => UserStatus::ELIMINADO,
        ]);

        $this->expectException(UserCannotBeDisabledException::class);
        UserValidator::ensureValidStatusTransition($user, UserStatus::BAJA);
    }

    #[Test]
    public function ensureValidStatusTransition_all_allowed_transitions(): void
    {
        // Probar todas las transiciones permitidas según el enum
        $allowedTransitions = [
            [UserStatus::ACTIVO, UserStatus::BAJA],
            [UserStatus::ACTIVO, UserStatus::BAJA_TEMPORAL],
            [UserStatus::ACTIVO, UserStatus::ELIMINADO],
            [UserStatus::BAJA, UserStatus::ACTIVO],
            [UserStatus::BAJA, UserStatus::ELIMINADO],
            [UserStatus::BAJA_TEMPORAL, UserStatus::ACTIVO],
            [UserStatus::BAJA_TEMPORAL, UserStatus::BAJA],
            [UserStatus::BAJA_TEMPORAL, UserStatus::ELIMINADO],
            [UserStatus::ELIMINADO, UserStatus::ACTIVO],
        ];

        foreach ($allowedTransitions as [$currentStatus, $newStatus]) {
            $user = $this->createMockUser([
                'status' => $currentStatus,
            ]);

            $this->expectNotToPerformAssertions();
            UserValidator::ensureValidStatusTransition($user, $newStatus);
        }
    }

    // Tests para ensureUserIsValidToUpdate
    #[Test]
    public function ensureUserIsValidToUpdate_passes_when_updatable(): void
    {
        $user = $this->createMockUser([
            'status' => UserStatus::ACTIVO, // Único estado actualizable
        ]);

        $this->expectNotToPerformAssertions();
        UserValidator::ensureUserIsValidToUpdate($user);
    }

    #[Test]
    public function ensureUserIsValidToUpdate_throws_when_not_updatable(): void
    {
        // Todos los estados que NO son actualizables
        $nonUpdatableStatuses = [
            UserStatus::BAJA,
            UserStatus::BAJA_TEMPORAL,
            UserStatus::ELIMINADO,
        ];

        foreach ($nonUpdatableStatuses as $status) {
            $user = $this->createMockUser([
                'status' => $status,
            ]);

            $this->expectException(UserCannotBeUpdatedException::class);
            UserValidator::ensureUserIsValidToUpdate($user);
        }
    }

    // Tests para ensureUserIsActive
    #[Test]
    public function ensureUserIsActive_passes_when_user_active(): void
    {
        $user = $this->createMockUser([
            'status' => UserStatus::ACTIVO,
        ]);

        $this->expectNotToPerformAssertions();
        UserValidator::ensureUserIsActive($user);
    }

    #[Test]
    public function ensureUserIsActive_throws_when_user_inactive(): void
    {
        // Todos los estados que NO son activos
        $inactiveStatuses = [
            UserStatus::BAJA,
            UserStatus::BAJA_TEMPORAL,
            UserStatus::ELIMINADO,
        ];

        foreach ($inactiveStatuses as $status) {
            $user = $this->createMockUser([
                'status' => $status,
            ]);

            $this->expectException(UserInactiveException::class);
            UserValidator::ensureUserIsActive($user);
        }
    }

    // Tests edge cases
    #[Test]
    public function ensureValidStatusTransition_order_of_validation(): void
    {
        // Primero verifica si es el mismo estado
        $user = $this->createMockUser([
            'status' => UserStatus::ACTIVO,
        ]);

        $this->expectException(UserAlreadyActiveException::class); // Mismo estado primero
        UserValidator::ensureValidStatusTransition($user, UserStatus::ACTIVO);
    }

    #[Test]
    public function ensureUserIsActive_with_multiple_inactive_states(): void
    {
        // Asegurar que todos los estados inactivos lanzan la excepción
        $inactiveStates = [
            UserStatus::BAJA,
            UserStatus::BAJA_TEMPORAL ,
            UserStatus::ELIMINADO ,
        ];

        foreach ($inactiveStates as $status) {
            $user = $this->createMockUser([
                'status' => $status,
            ]);

            $this->expectException(UserInactiveException::class);
            UserValidator::ensureUserIsActive($user);
        }
    }

    #[Test]
    public function ensureUserIsValidToUpdate_only_active_is_updatable(): void
    {
        // Verificar que solo ACTIVO es actualizable
        $testCases = [
            [UserStatus::ACTIVO, false],
            [UserStatus::BAJA, true],
            [UserStatus::BAJA_TEMPORAL, true],
            [UserStatus::ELIMINADO, true],
        ];

        foreach ($testCases as [$status, $expectsException]) {
            $user = $this->createMockUser([
                'status' => $status,
            ]);

            if ($expectsException) {
                $this->expectException(UserCannotBeUpdatedException::class);
            }

            UserValidator::ensureUserIsValidToUpdate($user);
        }
    }

    #[Test]
    public function ensureValidStatusTransition_uses_enum_logic(): void
    {
        $testCases = [
            [UserStatus::ACTIVO, UserStatus::BAJA, true],
            [UserStatus::ACTIVO, UserStatus::BAJA_TEMPORAL, true],
            [UserStatus::ACTIVO, UserStatus::ELIMINADO, true],
            [UserStatus::ACTIVO, UserStatus::ACTIVO, false], // Mismo estado
            [UserStatus::BAJA, UserStatus::ACTIVO, true],
            [UserStatus::BAJA, UserStatus::ELIMINADO, true],
            [UserStatus::BAJA, UserStatus::BAJA_TEMPORAL, false], // No permitido
            [UserStatus::BAJA_TEMPORAL, UserStatus::ACTIVO, true],
            [UserStatus::BAJA_TEMPORAL, UserStatus::BAJA, true],
            [UserStatus::BAJA_TEMPORAL, UserStatus::ELIMINADO, true],
            [UserStatus::ELIMINADO, UserStatus::ACTIVO, true],
            [UserStatus::ELIMINADO, UserStatus::BAJA, false], // No permitido
            [UserStatus::ELIMINADO, UserStatus::BAJA_TEMPORAL, false], // No permitido
        ];

        foreach ($testCases as [$currentStatus, $newStatus, $shouldPass]) {
            $user = $this->createMockUser(['status' => $currentStatus]);

            if ($shouldPass) {
                UserValidator::ensureValidStatusTransition($user, $newStatus);
            } else {
                $this->expectException(\Exception::class);
                UserValidator::ensureValidStatusTransition($user, $newStatus);
            }
        }
    }

    // Test para mensajes de error específicos
    #[Test]
    public function ensureValidStatusTransition_error_messages(): void
    {
        // Mismo estado -> excepciones específicas
        $sameStateTests = [
            [UserStatus::ACTIVO, UserAlreadyActiveException::class],
            [UserStatus::BAJA, UserAlreadyDisabledException::class],
            [UserStatus::BAJA_TEMPORAL, UserAlreadyDisabledException::class],
            [UserStatus::ELIMINADO, UserAlreadyDeletedException::class],
        ];

        foreach ($sameStateTests as [$status, $exceptionClass]) {
            $user = $this->createMockUser(['status' => $status]);
            $this->expectException($exceptionClass);
            UserValidator::ensureValidStatusTransition($user, $status);
        }

        // Transición no permitida a BAJA -> UserCannotBeDisabledException
        $user = $this->createMockUser(['status' => UserStatus::ELIMINADO]);
        $this->expectException(UserCannotBeDisabledException::class);
        UserValidator::ensureValidStatusTransition($user, UserStatus::BAJA);

        // Otras transiciones no permitidas -> UserConflictStatusException con mensaje
        $user = $this->createMockUser(['status' => UserStatus::BAJA]);
        try {
            UserValidator::ensureValidStatusTransition($user, UserStatus::BAJA_TEMPORAL);
            $this->fail('Expected UserConflictStatusException');
        } catch (UserConflictStatusException $e) {
            $this->assertStringContainsString('Transición inválida', $e->getMessage());
            $this->assertStringContainsString('baja', $e->getMessage());
            $this->assertStringContainsString('baja-temporal', $e->getMessage());
        }
    }
}
