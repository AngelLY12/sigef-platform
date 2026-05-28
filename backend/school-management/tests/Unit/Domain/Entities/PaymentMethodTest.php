<?php

namespace Tests\Unit\Domain\Entities;

use DateTime;
use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\PaymentMethod;
use PHPUnit\Framework\Attributes\Test;

class PaymentMethodTest extends BaseDomainTestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $paymentMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_123456789'
        );

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_parameters()
    {
        $paymentMethod = new PaymentMethod(
            user_id: 100,
            stripe_payment_method_id: 'pm_abcdefghij',
            brand: 'visa',
            last4: '4242',
            exp_month: 12,
            exp_year: 2025,
            id: 50
        );

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals(100, $paymentMethod->user_id);
        $this->assertEquals('pm_abcdefghij', $paymentMethod->stripe_payment_method_id);
        $this->assertEquals('visa', $paymentMethod->brand);
        $this->assertEquals('4242', $paymentMethod->last4);
        $this->assertEquals(12, $paymentMethod->exp_month);
        $this->assertEquals(2025, $paymentMethod->exp_year);
        $this->assertEquals(50, $paymentMethod->id);
    }

    #[Test]
    public function it_has_required_attributes()
    {
        $paymentMethod = new PaymentMethod(
            user_id: 42,
            stripe_payment_method_id: 'pm_required123'
        );

        $this->assertEquals(42, $paymentMethod->user_id);
        $this->assertEquals('pm_required123', $paymentMethod->stripe_payment_method_id);
        $this->assertNull($paymentMethod->brand);
        $this->assertNull($paymentMethod->last4);
        $this->assertNull($paymentMethod->exp_month);
        $this->assertNull($paymentMethod->exp_year);
        $this->assertNull($paymentMethod->id);
    }

    #[Test]
    public function it_accepts_valid_data()
    {
        $paymentMethod = new PaymentMethod(
            user_id: 25,
            stripe_payment_method_id: 'pm_valid999',
            brand: 'mastercard',
            last4: '8888',
            exp_month: 06,
            exp_year: 2024,
            id: 10
        );

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals(25, $paymentMethod->user_id);
        $this->assertEquals('pm_valid999', $paymentMethod->stripe_payment_method_id);
        $this->assertEquals('mastercard', $paymentMethod->brand);
        $this->assertEquals('8888', $paymentMethod->last4);
        $this->assertEquals(6, $paymentMethod->exp_month);
        $this->assertEquals(2024, $paymentMethod->exp_year);
        $this->assertEquals(10, $paymentMethod->id);
    }

    #[Test]
    public function it_detects_expired_payment_methods()
    {
        $currentYear = date('Y');

        $expiredLastYear = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_expired_last_year',
            exp_month: 12,
            exp_year: (int) ($currentYear - 1)
        );

        $this->assertTrue($expiredLastYear->isExpired());

        $validNextYear = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_valid_next_year',
            exp_month: 01,
            exp_year: (int) ($currentYear + 1)
        );

        $this->assertFalse($validNextYear->isExpired());

        $thisYearCard = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_this_year',
            exp_month: 12,
            exp_year: (int) $currentYear
        );

        $this->assertIsBool($thisYearCard->isExpired());
    }

    #[Test]
    public function it_handles_missing_expiration_data()
    {
        $noExpiration = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_no_exp'
        );

        $this->assertFalse($noExpiration->isExpired());

        $onlyMonth = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_month_only',
            exp_month: 12
        );

        $this->assertFalse($onlyMonth->isExpired());

        $onlyYear = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_year_only',
            exp_year: 2025
        );

        $this->assertFalse($onlyYear->isExpired());
    }

    #[Test]
    public function it_handles_invalid_expiration_format()
    {
        $invalidMonth = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_invalid_month',
            exp_month: '13',
            exp_year: 2025
        );

        $this->assertTrue($invalidMonth->isExpired());

        $zeroMonth = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_zero_month',
            exp_month: 0,
            exp_year: 2025
        );

        $this->assertFalse($zeroMonth->isExpired());

        $invalidYear = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_invalid_year',
            exp_month: 12,
            exp_year: null
        );

        $this->assertFalse($invalidYear->isExpired());
    }

    #[Test]
    public function it_calculates_expiration_date_correctly()
    {
        $currentMonth = date('m');
        $currentYear = date('Y');

        $paymentMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_current',
            exp_month: (int) $currentMonth,
            exp_year: (int) $currentYear
        );

        $expiration = DateTime::createFromFormat('Y-n', "{$currentYear}-{$currentMonth}");
        $this->assertNotFalse($expiration, 'Debe poder crear fecha de expiración');

        $expiration->modify('last day of this month 23:59:59');
        $now = new DateTime();

        $isExpired = $paymentMethod->isExpired();

        $shouldBeExpired = $now > $expiration;

        $this->assertEquals(
            $shouldBeExpired,
            $isExpired,
            sprintf(
                'Para fecha %s/%s, isExpired() debería devolver %s (ahora: %s, expira: %s)',
                $currentMonth,
                $currentYear,
                $shouldBeExpired ? 'true' : 'false',
                $now->format('Y-m-d H:i:s'),
                $expiration->format('Y-m-d H:i:s')
            )
        );

        $expirationDate = $paymentMethod->expirationDate();
        $expectedExpirationDate = sprintf('%02d/%s', $currentMonth, substr($currentYear, -2));
        $this->assertEquals($expectedExpirationDate, $expirationDate);
    }

    #[Test]
    public function it_returns_correct_expiration_date_string()
    {
        $paymentMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_test',
            exp_month: 9,
            exp_year: 2026
        );

        $this->assertEquals('09/26', $paymentMethod->expirationDate());

        $singleDigitMonth = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_single',
            exp_month: 5,
            exp_year: 2027
        );

        $this->assertEquals('05/27', $singleDigitMonth->expirationDate());

        $fourDigitYear = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_four_digit',
            exp_month: 12,
            exp_year: 2030
        );

        $this->assertEquals('12/30', $fourDigitYear->expirationDate());

        $twoDigitYear = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_two_digit',
            exp_month: 03,
            exp_year: 28
        );

        $this->assertEquals('03/28', $twoDigitYear->expirationDate());
    }

    #[Test]
    public function it_returns_na_for_missing_expiration_date()
    {
        $noDate = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_nodate'
        );

        $this->assertEquals('N/A', $noDate->expirationDate());

        $onlyMonth = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_month',
            exp_month: 12
        );

        $this->assertEquals('N/A', $onlyMonth->expirationDate());

        $onlyYear = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_year',
            exp_year: 2025
        );

        $this->assertEquals('N/A', $onlyYear->expirationDate());
    }

    #[Test]
    public function it_returns_masked_card_number()
    {
        // Con last4
        $paymentMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_masked',
            last4: '1234'
        );

        $this->assertEquals('**** **** **** 1234', $paymentMethod->getMaskedCard());

        $noLast4 = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_nomask'
        );

        $this->assertNull($noLast4->getMaskedCard());
    }

    #[Test]
    public function it_handles_different_brands()
    {
        $brands = [
            'visa',
            'mastercard',
            'amex',
            'discover',
            'jcb',
            'diners',
            'unionpay',
            null,
            'unknown_brand'
        ];

        foreach ($brands as $brand) {
            $paymentMethod = new PaymentMethod(
                user_id: 1,
                stripe_payment_method_id: 'pm_test',
                brand: $brand,
                last4: '9999'
            );

            $this->assertEquals($brand, $paymentMethod->brand);
            $this->assertEquals('**** **** **** 9999', $paymentMethod->getMaskedCard());
        }
    }

    #[Test]
    public function it_handles_different_last4_formats()
    {
        $last4s = [
            '1234',
            '0000',
            '9999',
            'abcd',
            '12',
            '12345',
            null,
        ];

        foreach ($last4s as $last4) {
            $paymentMethod = new PaymentMethod(
                user_id: 1,
                stripe_payment_method_id: 'pm_test',
                last4: $last4
            );

            $this->assertEquals($last4, $paymentMethod->last4);

            if ($last4) {
                $this->assertEquals("**** **** **** {$last4}", $paymentMethod->getMaskedCard());
            } else {
                $this->assertNull($paymentMethod->getMaskedCard());
            }
        }
    }

    #[Test]
    public function it_handles_edge_cases_for_expiration_months()
    {
        $months = [
            '01', '1',
            '02', '2',
            '03', '3',
            '04', '4',
            '05', '5',
            '06', '6',
            '07', '7',
            '08', '8',
            '09', '9',
            '10',
            '11',
            '12',
            '00',
            '13',
            null,
        ];

        foreach ($months as $month) {
            $paymentMethod = new PaymentMethod(
                user_id: 1,
                stripe_payment_method_id: 'pm_test',
                exp_month: (int) $month,
                exp_year: 2025
            );

            $this->assertEquals($month, $paymentMethod->exp_month);

            $paymentMethod->isExpired();
        }
    }

    #[Test]
    public function it_handles_edge_cases_for_expiration_years()
    {
        $years = [
            '2024',
            '2030',
            '2100',
            '99',
            '00',
            '2',
            null,
        ];

        foreach ($years as $year) {
            $paymentMethod = new PaymentMethod(
                user_id: 1,
                stripe_payment_method_id: 'pm_test',
                exp_month: 12,
                exp_year: (int) $year
            );

            $this->assertEquals($year, $paymentMethod->exp_year);

            $expDate = $paymentMethod->expirationDate();
            $this->assertIsString($expDate);
        }
    }

    #[Test]
    public function it_can_be_converted_to_json()
    {
        $paymentMethod = new PaymentMethod(
            user_id: 88,
            stripe_payment_method_id: 'pm_json',
            brand: 'discover',
            last4: '5555',
            exp_month: 07,
            exp_year: 2029,
            id: 33
        );

        $json = json_encode($paymentMethod);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(33, $decoded['id']);
        $this->assertEquals(88, $decoded['user_id']);
        $this->assertEquals('pm_json', $decoded['stripe_payment_method_id']);
        $this->assertEquals('discover', $decoded['brand']);
        $this->assertEquals('5555', $decoded['last4']);
        $this->assertEquals(07, $decoded['exp_month']);
        $this->assertEquals(2029, $decoded['exp_year']);
    }

    #[Test]
    public function it_handles_json_with_null_values()
    {
        $paymentMethod = new PaymentMethod(
            user_id: 66,
            stripe_payment_method_id: 'pm_nulls'
        );

        $json = json_encode($paymentMethod);
        $decoded = json_decode($json, true);

        $this->assertJson($json);
        $this->assertEquals(66, $decoded['user_id']);
        $this->assertEquals('pm_nulls', $decoded['stripe_payment_method_id']);
        $this->assertNull($decoded['id']);
        $this->assertNull($decoded['brand']);
        $this->assertNull($decoded['last4']);
        $this->assertNull($decoded['exp_month']);
        $this->assertNull($decoded['exp_year']);
    }

    #[Test]
    public function it_calculates_correct_expiration_for_february()
    {
        $febNonLeap = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_feb',
            exp_month: 02,
            exp_year: 2023
        );

        $febLeap = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_feb_leap',
            exp_month: 02,
            exp_year: 2024
        );

        $this->assertEquals('02/23', $febNonLeap->expirationDate());
        $this->assertEquals('02/24', $febLeap->expirationDate());

        $febNonLeap->isExpired();
        $febLeap->isExpired();
    }

    #[Test]
    public function it_handles_expiration_at_end_of_month()
    {
        $lastDay = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_last_day',
            exp_month: 12,
            exp_year: 2024
        );

        $expiration = DateTime::createFromFormat('Y-n', '2024-12');
        $expiration->modify('last day of this month 23:59:59');

        $now = new DateTime();
        $expectedExpired = $now > $expiration;

        $this->assertEquals($expectedExpired, $lastDay->isExpired());
    }

    #[Test]
    public function it_provides_correct_masked_card_for_various_last4()
    {
        $testCases = [
            ['last4' => '1234', 'expected' => '**** **** **** 1234'],
            ['last4' => '0000', 'expected' => '**** **** **** 0000'],
            ['last4' => '9999', 'expected' => '**** **** **** 9999'],
            ['last4' => 'ab12', 'expected' => '**** **** **** ab12'],
            ['last4' => '1', 'expected' => '**** **** **** 1'],
            ['last4' => '', 'expected' => null],
            ['last4' => null, 'expected' => null],
        ];

        foreach ($testCases as $test) {
            $paymentMethod = new PaymentMethod(
                user_id: 1,
                stripe_payment_method_id: 'pm_test',
                last4: $test['last4']
            );

            $this->assertEquals($test['expected'], $paymentMethod->getMaskedCard());
        }
    }

    #[Test]
    public function it_can_be_compared_by_stripe_id()
    {
        $pm1 = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_123'
        );

        $pm2 = new PaymentMethod(
            user_id: 2,
            stripe_payment_method_id: 'pm_123'
        );

        $pm3 = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_456'
        );

        $this->assertEquals($pm1->stripe_payment_method_id, $pm2->stripe_payment_method_id);
        $this->assertNotEquals($pm1->stripe_payment_method_id, $pm3->stripe_payment_method_id);
        $this->assertNotEquals($pm1->user_id, $pm2->user_id);
    }
}
