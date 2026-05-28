<?php

namespace Tests\Unit\Application\Mappers;

use App\Core\Application\DTO\Request\Mail\NewPaymentConceptEmailDTO;
use App\Core\Application\DTO\Request\Mail\NewUserCreatedEmailDTO;
use App\Core\Application\DTO\Request\Mail\PaymentCreatedEmailDTO;
use App\Core\Application\DTO\Request\Mail\PaymentFailedEmailDTO;
use App\Core\Application\DTO\Request\Mail\PaymentValidatedEmailDTO;
use App\Core\Application\DTO\Request\Mail\RequiresActionEmailDTO;
use App\Core\Application\DTO\Request\Mail\SendParentInviteEmailDTO;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Infraestructure\Mappers\PaymentMapper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailMapperTest extends TestCase
{
    use RefreshDatabase;

    // ==================== TO PAYMENT CREATED EMAIL DTO TESTS ====================

    #[Test]
    public function to_payment_created_email_dto_creates_correct_dto(): void
    {
        // Arrange
        Carbon::setTestNow('2024-01-15 10:30:00');

        $payment = new Payment(
            concept_name: 'Annual Subscription',
            amount: '150.00',
            status: PaymentStatus::PAID,
            url: 'https://example.com/payment/123',
            stripe_session_id: 'cs_test_123',
        );

        $recipientName = 'John Doe';
        $recipientEmail = 'john@example.com';

        // Act
        $result = MailMapper::toPaymentCreatedEmailDTO($payment, $recipientName, $recipientEmail);

        // Assert
        $this->assertInstanceOf(PaymentCreatedEmailDTO::class, $result);
        $this->assertEquals($recipientName, $result->recipientName);
        $this->assertEquals($recipientEmail, $result->recipientEmail);
        $this->assertEquals('Annual Subscription', $result->concept_name);
        $this->assertEquals('150.00', $result->amount);
        $this->assertEquals('2024-01-15 10:30:00', $result->created_at);
        $this->assertEquals('https://example.com/payment/123', $result->url);
        $this->assertEquals('cs_test_123', $result->stripe_session_id);
    }

    #[Test]
    public function to_payment_created_email_dto_uses_current_timestamp(): void
    {
        // Arrange
        $now = '2024-01-20 14:45:30';
        Carbon::setTestNow($now);

        $payment = new Payment(
            concept_name: 'Annual Subscription',
            amount: '150.00',
            status: PaymentStatus::PAID,
            url: 'https://example.com/payment/123',
            stripe_session_id: 'cs_test_123',
        );

        // Act
        $result = MailMapper::toPaymentCreatedEmailDTO($payment, 'Test', 'test@example.com');

        // Assert
        $this->assertEquals($now, $result->created_at);
    }

    // ==================== TO NEW PAYMENT CONCEPT EMAIL DTO TESTS ====================

    #[Test]
    public function to_new_payment_concept_email_dto_creates_correct_dto(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'Jane Doe',
            'recipientEmail' => 'jane@example.com',
            'concept_name' => 'Monthly Fee',
            'amount' => '50.00',
            'end_date' => '2024-12-31',
        ];

        // Act
        $result = MailMapper::toNewPaymentConceptEmailDTO($data);

        // Assert
        $this->assertInstanceOf(NewPaymentConceptEmailDTO::class, $result);
        $this->assertEquals('Jane Doe', $result->recipientName);
        $this->assertEquals('jane@example.com', $result->recipientEmail);
        $this->assertEquals('Monthly Fee', $result->concept_name);
        $this->assertEquals('50.00', $result->amount);
        $this->assertEquals('2024-12-31', $result->end_date);
    }


    // ==================== TO PAYMENT VALIDATED EMAIL DTO TESTS ====================

    #[Test]
    public function to_payment_validated_email_dto_creates_correct_dto(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'John Smith',
            'recipientEmail' => 'john.smith@example.com',
            'concept_name' => 'Course Enrollment',
            'amount' => '200.00',
            'amount_received' => '200.00',
            'status' => 'paid',
            'payment_method_detail' => ['Visa', '4242'],
            'payment_intent_id' => 'pi_123456',
            'url' => 'https://receipt.example.com/123',
        ];

        // Act
        $result = MailMapper::toPaymentValidatedEmailDTO($data);

        // Assert
        $this->assertInstanceOf(PaymentValidatedEmailDTO::class, $result);
        $this->assertEquals('John Smith', $result->recipientName);
        $this->assertEquals('john.smith@example.com', $result->recipientEmail);
        $this->assertEquals('Course Enrollment', $result->concept_name);
        $this->assertEquals('200.00', $result->amount);
        $this->assertEquals('200.00', $result->amount_received);
        $this->assertEquals('paid', $result->status);
        $this->assertEquals(['Visa', '4242'], $result->payment_method_detail);
        $this->assertEquals('pi_123456', $result->payment_intent_id);
        $this->assertEquals('https://receipt.example.com/123', $result->url);
    }

    #[Test]
    public function to_payment_validated_email_dto_with_partial_refund(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'Refund User',
            'recipientEmail' => 'refund@example.com',
            'concept_name' => 'Partial Refund',
            'amount' => '100.00',
            'amount_received' => '75.00', // Partial refund
            'status' => 'partially_refunded',
            'payment_method_detail' => ['Mastercard','8888'],
            'payment_intent_id' => 'pi_refund',
            'url' => 'https://receipt.example.com/refund',
        ];

        // Act
        $result = MailMapper::toPaymentValidatedEmailDTO($data);

        // Assert
        $this->assertInstanceOf(PaymentValidatedEmailDTO::class, $result);
        $this->assertEquals('100.00', $result->amount);
        $this->assertEquals('75.00', $result->amount_received);
        $this->assertEquals('partially_refunded', $result->status);
    }

    // ==================== TO PAYMENT FAILED EMAIL DTO TESTS ====================

    #[Test]
    public function to_payment_failed_email_dto_creates_correct_dto(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'Failed Payment User',
            'recipientEmail' => 'failed@example.com',
            'concept_name' => 'Membership Renewal',
            'amount' => '99.99',
            'error' => 'Card declined: insufficient funds',
        ];

        // Act
        $result = MailMapper::toPaymentFailedEmailDTO($data);

        // Assert
        $this->assertInstanceOf(PaymentFailedEmailDTO::class, $result);
        $this->assertEquals('Failed Payment User', $result->recipientName);
        $this->assertEquals('failed@example.com', $result->recipientEmail);
        $this->assertEquals('Membership Renewal', $result->concept_name);
        $this->assertEquals('99.99', $result->amount);
        $this->assertEquals('Card declined: insufficient funds', $result->error);
    }

    #[Test]
    public function to_payment_failed_email_dto_with_different_error_types(): void
    {
        $errorCases = [
            'Card declined: insufficient funds',
            'Expired card',
            'Invalid CVC',
            'Network error',
            'Timeout',
            'Account closed',
        ];

        foreach ($errorCases as $error) {
            $data = [
                'recipientName' => 'Test User',
                'recipientEmail' => 'test@example.com',
                'concept_name' => 'Test Payment',
                'amount' => '10.00',
                'error' => $error,
            ];

            $result = MailMapper::toPaymentFailedEmailDTO($data);
            $this->assertEquals($error, $result->error);
        }
    }

    // ==================== TO REQUIRES ACTION EMAIL DTO TESTS ====================

    #[Test]
    public function to_requires_action_email_dto_creates_correct_dto(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'Action Required User',
            'recipientEmail' => 'action@example.com',
            'concept_name' => '3D Secure Payment',
            'amount' => '150.50',
            'next_action' => ['complete_authentication'],
            'payment_method_options' => ['card' => ['three_d_secure' => ['required' => true]]],
        ];

        // Act
        $result = MailMapper::toRequiresActionEmailDTO($data);

        // Assert
        $this->assertInstanceOf(RequiresActionEmailDTO::class, $result);
        $this->assertEquals('Action Required User', $result->recipientName);
        $this->assertEquals('action@example.com', $result->recipientEmail);
        $this->assertEquals('3D Secure Payment', $result->concept_name);
        $this->assertEquals('150.50', $result->amount);
        $this->assertEquals(['complete_authentication'], $result->next_action);
        $this->assertEquals(['card' => ['three_d_secure' => ['required' => true]]], $result->payment_method_options);
    }

    #[Test]
    public function to_requires_action_email_dto_with_simple_payment_method_options(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'Simple Action',
            'recipientEmail' => 'simple@example.com',
            'concept_name' => 'Simple Payment',
            'amount' => '50.00',
            'next_action' => ['confirm_payment'],
            'payment_method_options' => ['bank_transfer' => ['type' => 'us_bank_transfer']],
        ];

        // Act
        $result = MailMapper::toRequiresActionEmailDTO($data);

        // Assert
        $this->assertEquals(['confirm_payment'], $result->next_action);
        $this->assertEquals(['bank_transfer' => ['type' => 'us_bank_transfer']], $result->payment_method_options);
    }

    // ==================== TO NEW USER CREATED EMAIL DTO TESTS ====================

    #[Test]
    public function to_new_user_created_email_dto_creates_correct_dto(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'New User',
            'recipientEmail' => 'newuser@example.com',
            'password' => 'temporaryPassword123',
        ];

        // Act
        $result = MailMapper::toNewUserCreatedEmailDTO($data);

        // Assert
        $this->assertInstanceOf(NewUserCreatedEmailDTO::class, $result);
        $this->assertEquals('New User', $result->recipientName);
        $this->assertEquals('newuser@example.com', $result->recipientEmail);
        $this->assertEquals('temporaryPassword123', $result->password);
    }

    #[Test]
    public function to_new_user_created_email_dto_with_encrypted_password(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'Encrypted User',
            'recipientEmail' => 'encrypted@example.com',
            'password' => '$2y$10$hashedpasswordstring1234567890', // BCrypt hash
        ];

        // Act
        $result = MailMapper::toNewUserCreatedEmailDTO($data);

        // Assert
        $this->assertInstanceOf(NewUserCreatedEmailDTO::class, $result);
        $this->assertEquals('$2y$10$hashedpasswordstring1234567890', $result->password);
    }

    // ==================== TO SEND PARENT INVITE EMAIL DTO TESTS ====================

    #[Test]
    public function to_send_parent_invite_email_dto_creates_correct_dto(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'Parent Name',
            'recipientEmail' => 'parent@example.com',
            'token' => 'invite_token_abc123',
        ];

        // Act
        $result = MailMapper::toSendParentInviteEmail($data);

        // Assert
        $this->assertInstanceOf(SendParentInviteEmailDTO::class, $result);
        $this->assertEquals('Parent Name', $result->recipientName);
        $this->assertEquals('parent@example.com', $result->recipientEmail);
        $this->assertEquals('invite_token_abc123', $result->token);
    }

    #[Test]
    public function to_send_parent_invite_email_dto_with_secure_token(): void
    {
        // Arrange
        $data = [
            'recipientName' => 'Secure Parent',
            'recipientEmail' => 'secure@example.com',
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IlBhcmVudCIsImlhdCI6MTUxNjIzOTAyMn0.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c', // JWT token
        ];

        // Act
        $result = MailMapper::toSendParentInviteEmail($data);

        // Assert
        $this->assertInstanceOf(SendParentInviteEmailDTO::class, $result);
        $this->assertStringStartsWith('eyJ', $result->token); // JWT starts with eyJ
    }

    // ==================== EDGE CASE TESTS ====================

    #[Test]
    public function payment_created_email_dto_always_uses_current_time(): void
    {
        // Test que verifica que siempre se usa Carbon::now()
        $testTimes = [
            '2024-01-01 00:00:00',
            '2024-06-15 12:30:45',
            '2024-12-31 23:59:59',
        ];

        foreach ($testTimes as $testTime) {
            Carbon::setTestNow($testTime);

            $payment = new Payment(concept_name: 'Annual Subscription',
                amount: '150.00',
                status: PaymentStatus::PAID,
                url: 'https://example.com/payment/123',
                stripe_session_id: 'cs_test_123',
            );

            $result = MailMapper::toPaymentCreatedEmailDTO($payment, 'Test', 'test@example.com');
            $this->assertEquals($testTime, $result->created_at);
        }
    }

    #[Test]
    public function handles_special_characters_in_data(): void
    {
        // Arrange
        $specialData = [
            'recipientName' => 'María José Pérez-López',
            'recipientEmail' => 'maría.josé@example.com',
            'concept_name' => 'Curso de Matemáticas 101 - Álgebra',
            'amount' => '1,000.50', // Con coma
            'error' => 'Error: Tarjeta inválida (código: #123-ABC)',
            'password' => 'contraseñaSegura123!@#',
            'token' => 'token-ñandú-123',
        ];

        // Test con diferentes métodos
        $paymentFailedResult = MailMapper::toPaymentFailedEmailDTO($specialData);
        $this->assertEquals('María José Pérez-López', $paymentFailedResult->recipientName);
        $this->assertEquals('maría.josé@example.com', $paymentFailedResult->recipientEmail);
        $this->assertEquals('Curso de Matemáticas 101 - Álgebra', $paymentFailedResult->concept_name);
        $this->assertEquals('1,000.50', $paymentFailedResult->amount);
        $this->assertEquals('Error: Tarjeta inválida (código: #123-ABC)', $paymentFailedResult->error);

        $newUserResult = MailMapper::toNewUserCreatedEmailDTO($specialData);
        $this->assertEquals('contraseñaSegura123!@#', $newUserResult->password);

        $parentInviteResult = MailMapper::toSendParentInviteEmail($specialData);
        $this->assertEquals('token-ñandú-123', $parentInviteResult->token);
    }

    #[Test]
    public function payment_amounts_with_different_formats(): void
    {
        $amountTestCases = [
            ['input' => '100.00', 'expected' => '100.00'],
            ['input' => '100', 'expected' => '100'],
            ['input' => '1,000.50', 'expected' => '1,000.50'],
            ['input' => '0.00', 'expected' => '0.00'],
            ['input' => '999999.99', 'expected' => '999999.99'],
            ['input' => '100.5', 'expected' => '100.5'],
        ];

        foreach ($amountTestCases as $case) {
            $data = [
                'recipientName' => 'Test',
                'recipientEmail' => 'test@example.com',
                'concept_name' => 'Test Payment',
                'amount' => $case['input'],
                'error' => 'Test error',
            ];

            $result = MailMapper::toPaymentFailedEmailDTO($data);
            $this->assertEquals($case['expected'], $result->amount,
                "Failed for amount: {$case['input']}");
        }
    }

    // ==================== INTEGRATION TESTS ====================

    #[Test]
    public function can_be_used_in_real_email_scenarios(): void
    {
        // Escenario 1: Pago creado exitosamente
        Carbon::setTestNow('2024-01-20 09:00:00');
        $payment = new Payment(
            concept_name: 'Annual Subscription',
            amount: '150.00',
            status: PaymentStatus::PAID,
            url: 'https://example.com/payment/123',
            stripe_session_id: 'cs_test_123',
        );

        $paymentCreatedDTO = MailMapper::toPaymentCreatedEmailDTO(
            $payment,
            'Student Name',
            'student@example.com'
        );

        $this->assertEquals('Annual Subscription', $paymentCreatedDTO->concept_name);
        $this->assertEquals('150.00', $paymentCreatedDTO->amount);
        $this->assertEquals('2024-01-20 09:00:00', $paymentCreatedDTO->created_at);

        // Escenario 2: Pago fallido
        $paymentFailedDTO = MailMapper::toPaymentFailedEmailDTO([
            'recipientName' => 'Student Name',
            'recipientEmail' => 'student@example.com',
            'concept_name' => 'Semester Tuition',
            'amount' => '5000.00',
            'error' => 'Insufficient funds',
        ]);

        $this->assertEquals('Insufficient funds', $paymentFailedDTO->error);

        // Escenario 3: Nuevo usuario
        $newUserDTO = MailMapper::toNewUserCreatedEmailDTO([
            'recipientName' => 'New Teacher',
            'recipientEmail' => 'teacher@example.com',
            'password' => 'Welcome123',
        ]);

        $this->assertEquals('Welcome123', $newUserDTO->password);
    }

    #[Test]
    public function mapper_works_with_real_payment_model(): void
    {
        // Arrange - Usando un Payment real de la base de datos
        $payment = \App\Models\Payment::factory()->create([
            'concept_name' => 'Factory Payment',
            'amount' => '75.50',
            'url' => 'https://example.com/factory',
            'stripe_session_id' => 'cs_factory_123',
        ]);

        Carbon::setTestNow('2024-01-25 14:30:00');

        // Act
        $result = MailMapper::toPaymentCreatedEmailDTO(
            PaymentMapper::toDomain($payment),
            'Factory User',
            'factory@example.com'
        );

        // Assert
        $this->assertInstanceOf(PaymentCreatedEmailDTO::class, $result);
        $this->assertEquals('Factory Payment', $result->concept_name);
        $this->assertEquals('75.50', $result->amount);
        $this->assertEquals('https://example.com/factory', $result->url);
        $this->assertEquals('cs_factory_123', $result->stripe_session_id);
        $this->assertEquals('2024-01-25 14:30:00', $result->created_at);
    }

    // ==================== HELPER METHOD ====================

    /**
     * Helper para obtener la clase DTO correspondiente a un método
     */
    private function getDTOClassForMethod(string $method): string
    {
        $methodToClass = [
            'toPaymentCreatedEmailDTO' => PaymentCreatedEmailDTO::class,
            'toNewPaymentConceptEmailDTO' => NewPaymentConceptEmailDTO::class,
            'toPaymentValidatedEmailDTO' => PaymentValidatedEmailDTO::class,
            'toPaymentFailedEmailDTO' => PaymentFailedEmailDTO::class,
            'toRequiresActionEmailDTO' => RequiresActionEmailDTO::class,
            'toNewUserCreatedEmailDTO' => NewUserCreatedEmailDTO::class,
            'toSendParentInviteEmail' => SendParentInviteEmailDTO::class,
        ];

        return $methodToClass[$method] ?? throw new \InvalidArgumentException("Unknown method: {$method}");
    }

    #[Test]
    public function handles_large_amounts_in_payments(): void
    {
        // Arrange - Montos grandes pero realistas
        $largePayment = new Payment(concept_name: 'Annual Subscription',
            amount: '999999.99',
            status: PaymentStatus::PAID,
            url: 'https://example.com/payment/123',
            stripe_session_id: 'cs_test_123',);

        Carbon::setTestNow('2024-01-01 00:00:00');

        // Act
        $result = MailMapper::toPaymentCreatedEmailDTO(
            $largePayment,
            'Corporate User',
            'corporate@example.com'
        );

        // Assert
        $this->assertEquals('999999.99', $result->amount);
    }

    #[Test]
    public function handles_multiple_recipients_scenario(): void
    {
        // Test que simula envío a múltiples destinatarios
        $payments = [
            [
                'concept_name' => 'Class A',
                'amount' => '100.00',
                'recipient' => 'Student A',
                'email' => 'student.a@example.com',
            ],
            [
                'concept_name' => 'Class B',
                'amount' => '150.00',
                'recipient' => 'Student B',
                'email' => 'student.b@example.com',
            ],
            [
                'concept_name' => 'Class C',
                'amount' => '200.00',
                'recipient' => 'Student C',
                'email' => 'student.c@example.com',
            ],
        ];

        $dtos = [];
        foreach ($payments as $paymentData) {
            $payment = new Payment(
                concept_name: $paymentData['concept_name'],
            amount: $paymentData['amount'],
            status: PaymentStatus::PAID,
            url: 'https://example.com/payment/123',
            stripe_session_id: 'cs_test_123',
            );

            $dto = MailMapper::toPaymentCreatedEmailDTO(
                $payment,
                $paymentData['recipient'],
                $paymentData['email']
            );

            $dtos[] = $dto;
        }

        $this->assertCount(3, $dtos);
        $this->assertEquals('Student A', $dtos[0]->recipientName);
        $this->assertEquals('Student B', $dtos[1]->recipientName);
        $this->assertEquals('Student C', $dtos[2]->recipientName);
        $this->assertEquals('100.00', $dtos[0]->amount);
        $this->assertEquals('150.00', $dtos[1]->amount);
        $this->assertEquals('200.00', $dtos[2]->amount);
    }

    #[Test]
    public function payment_method_options_can_be_complex_arrays(): void
    {
        // Arrange - Opciones de pago complejas
        $complexOptions = [
            'card' => [
                'three_d_secure' => [
                    'required' => true,
                    'version' => '2.1.0',
                ],
                'installments' => [
                    'enabled' => true,
                    'plan' => 'fixed_count',
                    'count' => 3,
                ],
            ],
            'oxxo' => [
                'expires_after_days' => 7,
            ],
        ];

        $data = [
            'recipientName' => 'Complex Payment User',
            'recipientEmail' => 'complex@example.com',
            'concept_name' => 'Complex Payment',
            'amount' => '300.00',
            'next_action' => ['handle_3ds_and_installments'],
            'payment_method_options' => $complexOptions,
        ];

        // Act
        $result = MailMapper::toRequiresActionEmailDTO($data);

        // Assert
        $this->assertEquals($complexOptions, $result->payment_method_options);
        $this->assertTrue($result->payment_method_options['card']['three_d_secure']['required']);
        $this->assertEquals(3, $result->payment_method_options['card']['installments']['count']);
        $this->assertEquals(7, $result->payment_method_options['oxxo']['expires_after_days']);
    }

    #[Test]
    public function handles_different_payment_statuses(): void
    {
        $statuses = [
            'paid',
            'unpaid',
            'partially_paid',
            'refunded',
            'partially_refunded',
            'requires_action',
            'processing',
            'canceled',
        ];

        foreach ($statuses as $status) {
            $data = [
                'recipientName' => 'Status Test',
                'recipientEmail' => 'test@example.com',
                'concept_name' => 'Status Payment',
                'amount' => '50.00',
                'amount_received' => '50.00',
                'status' => $status,
                'payment_method_detail' => ['Test Card'],
                'payment_intent_id' => 'pi_test',
                'url' => 'https://example.com',
            ];

            $result = MailMapper::toPaymentValidatedEmailDTO($data);
            $this->assertEquals($status, $result->status);
        }
    }

}
