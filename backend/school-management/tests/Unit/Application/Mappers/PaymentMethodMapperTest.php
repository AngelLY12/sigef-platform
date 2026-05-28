<?php

namespace Tests\Unit\Application\Mappers;

use App\Core\Application\DTO\Response\PaymentMethod\DisplayPaymentMethodResponse;
use App\Core\Application\DTO\Response\PaymentMethod\SetupCardResponse;
use App\Core\Application\Mappers\PaymentMethodMapper;
use PHPUnit\Framework\Attributes\Test;
use App\Core\Domain\Entities\PaymentMethod as DomainPaymentMethod;
use Stripe\Checkout\Session;
use Tests\TestCase;

class PaymentMethodMapperTest extends TestCase
{
    // ==================== TO DISPLAY PAYMENT METHOD RESPONSE TESTS ====================

    #[Test]
    public function to_display_payment_method_response_creates_response_from_domain_method(): void
    {
        // Arrange
        $method = $this->createMock(DomainPaymentMethod::class);
        $method->id = 123;
        $method->brand = 'visa';

        // Configurar método para obtener tarjeta enmascarada
        $method->method('getMaskedCard')->willReturn('**** **** **** 4242');

        // Configurar método para fecha de expiración
        $method->method('expirationDate')->willReturn('12/25');

        // Configurar método para verificar si está expirada
        $method->method('isExpired')->willReturn(false);

        // Act
        $result = PaymentMethodMapper::toDisplayPaymentMethodResponse($method);

        // Assert
        $this->assertInstanceOf(DisplayPaymentMethodResponse::class, $result);
        $this->assertEquals(123, $result->id);
        $this->assertEquals('visa', $result->brand);
        $this->assertEquals('**** **** **** 4242', $result->masked_card);
        $this->assertEquals('12/25', $result->expiration_date);
        $this->assertEquals('Vigente', $result->status);
    }

    #[Test]
    public function to_display_payment_method_response_with_expired_method(): void
    {
        // Arrange
        $method = $this->createMock(DomainPaymentMethod::class);
        $method->id = 456;
        $method->brand = 'mastercard';
        $method->method('getMaskedCard')->willReturn('**** **** **** 1234');
        $method->method('expirationDate')->willReturn('01/23'); // Fecha pasada
        $method->method('isExpired')->willReturn(true); // Expired

        // Act
        $result = PaymentMethodMapper::toDisplayPaymentMethodResponse($method);

        // Assert
        $this->assertInstanceOf(DisplayPaymentMethodResponse::class, $result);
        $this->assertEquals(456, $result->id);
        $this->assertEquals('mastercard', $result->brand);
        $this->assertEquals('**** **** **** 1234', $result->masked_card);
        $this->assertEquals('01/23', $result->expiration_date);
        $this->assertEquals('Caducada', $result->status);
    }

    #[Test]
    public function to_display_payment_method_response_with_null_values(): void
    {
        // Arrange
        $method = $this->createMock(DomainPaymentMethod::class);
        $method->id = null;
        $method->brand = null;
        $method->method('getMaskedCard')->willReturn(null);
        $method->method('expirationDate')->willReturn('N/A');
        $method->method('isExpired')->willReturn(false); // Asumir no expirada si no hay fecha

        // Act
        $result = PaymentMethodMapper::toDisplayPaymentMethodResponse($method);

        // Assert
        $this->assertInstanceOf(DisplayPaymentMethodResponse::class, $result);
        $this->assertNull($result->id);
        $this->assertEquals('Desconocido', $result->brand); // Valor por defecto
        $this->assertEquals('**** **** **** ****', $result->masked_card); // Valor por defecto
        $this->assertEquals('N/A',$result->expiration_date);
        $this->assertEquals('Vigente', $result->status); // No expirada por defecto
    }

    // ==================== TO SETUP CARD RESPONSE TESTS ====================

    #[Test]
    public function to_setup_card_response_creates_response_from_stripe_session(): void
    {
        // Arrange
        $session = $this->createStripeSession(
            [
                'id' => 'cs_test_123456789',
                'url' => 'https://checkout.stripe.com/setup/c/pay_123'
            ]
        );

        // Act
        $result = PaymentMethodMapper::toSetupCardResponse($session);

        // Assert
        $this->assertInstanceOf(SetupCardResponse::class, $result);
        $this->assertEquals('cs_test_123456789', $result->id);
        $this->assertEquals('https://checkout.stripe.com/setup/c/pay_123', $result->url);
    }


    #[Test]
    public function to_setup_card_response_with_empty_strings(): void
    {
        // Arrange
        $session = $this->createMock(Session::class);
        $session->id = '';
        $session->url = '';

        // Act
        $result = PaymentMethodMapper::toSetupCardResponse($session);

        // Assert
        $this->assertInstanceOf(SetupCardResponse::class, $result);
        $this->assertEquals('', $result->id);
        $this->assertEquals('', $result->url);
    }

    // ==================== EDGE CASES TESTS ====================

    #[Test]
    public function to_display_payment_method_response_with_unknown_brand(): void
    {
        // Arrange
        $method = $this->createMock(DomainPaymentMethod::class);
        $method->id = 999;
        $method->brand = ''; // Marca vacía
        $method->method('getMaskedCard')->willReturn('**** **** **** 9999');
        $method->method('expirationDate')->willReturn('12/25');
        $method->method('isExpired')->willReturn(false);

        // Act
        $result = PaymentMethodMapper::toDisplayPaymentMethodResponse($method);

        // Assert
        $this->assertInstanceOf(DisplayPaymentMethodResponse::class, $result);
        $this->assertEquals('', $result->brand); // Cadena vacía, no 'Desconocido'
        $this->assertEquals('**** **** **** 9999', $result->masked_card);
        $this->assertEquals('Vigente', $result->status);
    }

    #[Test]
    public function to_display_payment_method_response_with_partial_masked_card(): void
    {
        // Arrange
        $method = $this->createMock(DomainPaymentMethod::class);
        $method->id = 777;
        $method->brand = 'amex';
        $method->method('getMaskedCard')->willReturn('**** ****** *1234'); // Formato Amex
        $method->method('expirationDate')->willReturn('06/24');
        $method->method('isExpired')->willReturn(true); // Expired

        // Act
        $result = PaymentMethodMapper::toDisplayPaymentMethodResponse($method);

        // Assert
        $this->assertInstanceOf(DisplayPaymentMethodResponse::class, $result);
        $this->assertEquals(777, $result->id);
        $this->assertEquals('amex', $result->brand);
        $this->assertEquals('**** ****** *1234', $result->masked_card); // Mantiene formato Amex
        $this->assertEquals('06/24', $result->expiration_date);
        $this->assertEquals('Caducada', $result->status);
    }

    #[Test]
    public function to_setup_card_response_with_long_url(): void
    {
        // Arrange
        $longUrl = 'https://checkout.stripe.com/setup/c/pay_' . str_repeat('a', 100) . '/test';

        $session = $this->createStripeSession(
            [
                'id' => 'cs_test_' . str_repeat('b', 50),
                'url' => $longUrl
            ]
        );

        // Act
        $result = PaymentMethodMapper::toSetupCardResponse($session);

        // Assert
        $this->assertInstanceOf(SetupCardResponse::class, $result);
        $this->assertEquals('cs_test_' . str_repeat('b', 50), $result->id);
        $this->assertEquals($longUrl, $result->url);
    }

    #[Test]
    public function to_display_payment_method_response_status_logic(): void
    {
        // Test 1: No expirada
        $method1 = $this->createMock(DomainPaymentMethod::class);
        $method1->id = 1;
        $method1->brand = 'visa';
        $method1->method('getMaskedCard')->willReturn('**** **** **** 1111');
        $method1->method('expirationDate')->willReturn('12/99'); // Lejano futuro
        $method1->method('isExpired')->willReturn(false);

        $result1 = PaymentMethodMapper::toDisplayPaymentMethodResponse($method1);
        $this->assertEquals('Vigente', $result1->status);

        // Test 2: Expirada
        $method2 = $this->createMock(DomainPaymentMethod::class);
        $method2->id = 2;
        $method2->brand = 'mastercard';
        $method2->method('getMaskedCard')->willReturn('**** **** **** 2222');
        $method2->method('expirationDate')->willReturn('01/20'); // Pasado
        $method2->method('isExpired')->willReturn(true);

        $result2 = PaymentMethodMapper::toDisplayPaymentMethodResponse($method2);
        $this->assertEquals('Caducada', $result2->status);

        // Test 3: Sin fecha de expiración
        $method3 = $this->createMock(DomainPaymentMethod::class);
        $method3->id = 3;
        $method3->brand = 'amex';
        $method3->method('getMaskedCard')->willReturn('**** ****** *3333');
        $method3->method('expirationDate')->willReturn('N/A'); // Null
        $method3->method('isExpired')->willReturn(false); // Asume no expirada

        $result3 = PaymentMethodMapper::toDisplayPaymentMethodResponse($method3);
        $this->assertEquals('Vigente', $result3->status);
    }
    private function createStripeSession(array $data = []): Session
    {
        $sessionData = array_merge([
            'id' => $data['id'] ?? 'cs_test_123',
            'payment_intent' => $data['payment_intent'] ?? 'pi_123',
            'payment_status_detailed' => $data['payment_status_detailed'] ?? 'paid',
            'payment_status' => $data['payment_status'] ?? 'paid',
            'amount_total' => $data['amount_total'] ?? 15000,
            'amount_received' => $data['amount_received'] ?? 15000,
            'created' => $data['created'] ?? 1672531200,
            'url' => $data['url'] ?? 'https://receipt.stripe.com/test',
            'metadata' => $data['metadata'] ?? ['concept_name' => 'Annual Subscription'],
        ], $data);

        $session = \Stripe\Util\Util::convertToStripeObject($sessionData, ['api_key' => null]);

        $session = Session::constructFrom($sessionData);

        return $session;
    }


}
