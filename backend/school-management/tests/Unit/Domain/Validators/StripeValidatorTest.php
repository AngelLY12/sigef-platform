<?php

namespace Tests\Unit\Domain\Validators;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Utils\Validators\StripeValidator;
use App\Exceptions\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class StripeValidatorTest extends TestCase
{
    private function createMockUser(array $properties = []): MockObject
    {
        $mock = $this->createMock(User::class);

        $defaults = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'curp' => 'TEST123456',
            'last_name' => 'Doe',
            'password' => 'password',
            'phone_number' => '1234567890',
        ];

        $properties = array_merge($defaults, $properties);

        // Configurar propiedades públicas
        $mock->id = $properties['id'];
        $mock->name = $properties['name'];
        $mock->email = $properties['email'];
        $mock->curp = $properties['curp'];
        $mock->last_name = $properties['last_name'];
        $mock->password = $properties['password'];
        $mock->phone_number = $properties['phone_number'];

        return $mock;
    }

    // Tests para validateUserForStripe
    #[Test]
    public function validateUserForStripe_passes_with_valid_user(): void
    {
        $user = $this->createMockUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->expectNotToPerformAssertions();
        StripeValidator::validateUserForStripe($user);
    }

    #[Test]
    public function validateUserForStripe_throws_when_email_empty(): void
    {
        $user = $this->createMockUser([
            'name' => 'John Doe',
            'email' => '', // Email vacío
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El usuario no tiene correo electrónico');
        StripeValidator::validateUserForStripe($user);
    }

    #[Test]
    public function validateUserForStripe_throws_when_name_empty(): void
    {
        $user = $this->createMockUser([
            'name' => '', // Nombre vacío
            'email' => 'john@example.com',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El usuario no tiene nombre definido');
        StripeValidator::validateUserForStripe($user);
    }

    #[Test]
    public function validateUserForStripe_throws_when_both_email_and_name_empty(): void
    {
        $user = $this->createMockUser([
            'name' => '', // Nombre vacío
            'email' => '', // Email vacío
        ]);

        // Debería lanzar primero el error del email (primer check)
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El usuario no tiene correo electrónico');
        StripeValidator::validateUserForStripe($user);
    }

    // Tests para validateStripeId
    #[Test]
    public function validateStripeId_passes_with_valid_id(): void
    {
        $testCases = [
            ['cus_1234567890abcdef', 'cus', 'cliente'],
            ['pm_1234567890abcdef', 'pm', 'método de pago'],
            ['pi_1234567890abcdef', 'pi', 'intención de pago'],
            ['sub_1234567890abcdef', 'sub', 'suscripción'],
        ];

        foreach ($testCases as [$id, $prefix, $fieldName]) {
            $this->expectNotToPerformAssertions();
            StripeValidator::validateStripeId($id, $prefix, $fieldName);
        }
    }

    #[Test]
    public function validateStripeId_throws_when_id_empty(): void
    {
        $testCases = [
            [null, 'cus', 'cliente'],
            ['', 'cus', 'cliente'],
            ['   ', 'cus', 'cliente'],
        ];

        foreach ($testCases as [$id, $prefix, $fieldName]) {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage("El ID de {$fieldName} no puede ser vacío");
            StripeValidator::validateStripeId($id, $prefix, $fieldName);
        }
    }

    #[Test]
    public function validateStripeId_throws_when_wrong_prefix(): void
    {
        // ID con prefijo incorrecto
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El ID de cliente tiene un formato inválido');
        StripeValidator::validateStripeId('pm_123456', 'cus', 'cliente');
    }

    #[Test]
    public function validateStripeId_throws_when_invalid_format(): void
    {
        $invalidCases = [
            ['cus_', 'cus', 'cliente'], // Sin sufijo
            ['cus_!@#$%', 'cus', 'cliente'], // Caracteres especiales
            ['123456', 'cus', 'cliente'], // Sin prefijo
            ['cus_123 456', 'cus', 'cliente'], // Con espacio
            ['cus-123456', 'cus', 'cliente'], // Guión en lugar de underscore
        ];

        foreach ($invalidCases as [$id, $prefix, $fieldName]) {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage("El ID de {$fieldName} tiene un formato inválido");
            StripeValidator::validateStripeId($id, $prefix, $fieldName);
        }
    }

    #[Test]
    public function validateStripeId_passes_with_complex_suffix(): void
    {
        // IDs con sufijos complejos pero válidos
        $validCases = [
            ['cus_1234567890abcdefABCDEF012345', 'cus', 'cliente'], // Mix de números y letras
            ['cus_a1b2c3d4e5f6', 'cus', 'cliente'], // Letras y números intercalados
            ['cus_0123456789', 'cus', 'cliente'], // Solo números
            ['cus_abcdefABCDEF', 'cus', 'cliente'], // Solo letras
        ];

        foreach ($validCases as [$id, $prefix, $fieldName]) {
            $this->expectNotToPerformAssertions();
            StripeValidator::validateStripeId($id, $prefix, $fieldName);
        }
    }
}
