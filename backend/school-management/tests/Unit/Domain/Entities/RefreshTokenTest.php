<?php

namespace Tests\Unit\Domain\Entities;

use Carbon\CarbonImmutable;
use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\RefreshToken;
use PHPUnit\Framework\Attributes\Test;

class RefreshTokenTest extends BaseDomainTestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $refreshToken = new RefreshToken(
            id: 1,
            user_id: 100,
            token: 'refresh_token_123',
            expiresAt: CarbonImmutable::now()->addDays(7)
        );

        $this->assertInstanceOf(RefreshToken::class, $refreshToken);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_parameters()
    {
        $expiresAt = CarbonImmutable::now()->addDays(30);
        $refreshToken = new RefreshToken(
            id: 5,
            user_id: 42,
            token: 'refresh_token_abc_xyz',
            expiresAt: $expiresAt,
            revoked: true
        );

        $this->assertInstanceOf(RefreshToken::class, $refreshToken);
        $this->assertEquals(5, $refreshToken->id);
        $this->assertEquals(42, $refreshToken->user_id);
        $this->assertEquals('refresh_token_abc_xyz', $refreshToken->token);
        $this->assertEquals($expiresAt, $refreshToken->expiresAt);
        $this->assertTrue($refreshToken->revoked);
    }

    #[Test]
    public function it_has_required_attributes()
    {
        $expiresAt = CarbonImmutable::now()->addDays(1);
        $refreshToken = new RefreshToken(
            id: 10,
            user_id: 50,
            token: 'test_token',
            expiresAt: $expiresAt
        );

        $this->assertEquals(10, $refreshToken->id);
        $this->assertEquals(50, $refreshToken->user_id);
        $this->assertEquals('test_token', $refreshToken->token);
        $this->assertEquals($expiresAt, $refreshToken->expiresAt);
        $this->assertFalse($refreshToken->revoked);
    }

    #[Test]
    public function it_accepts_valid_data()
    {
        $expiresAt = CarbonImmutable::createFromDate(2024, 12, 31);
        $refreshToken = new RefreshToken(
            id: 3,
            user_id: 25,
            token: 'valid_refresh_token',
            expiresAt: $expiresAt,
            revoked: false
        );

        $this->assertInstanceOf(RefreshToken::class, $refreshToken);
        $this->assertEquals(3, $refreshToken->id);
        $this->assertEquals(25, $refreshToken->user_id);
        $this->assertEquals('valid_refresh_token', $refreshToken->token);
        $this->assertEquals($expiresAt, $refreshToken->expiresAt);
        $this->assertFalse($refreshToken->revoked);
    }

    #[Test]
    public function it_detects_expired_tokens()
    {
        $expiredToken = new RefreshToken(
            id: 1,
            user_id: 1,
            token: 'expired_token',
            expiresAt: CarbonImmutable::now()->subDay()
        );

        $this->assertTrue($expiredToken->isExpired());

        $validToken = new RefreshToken(
            id: 2,
            user_id: 1,
            token: 'valid_token',
            expiresAt: CarbonImmutable::now()->addDay()
        );

        $this->assertFalse($validToken->isExpired());
    }

    #[Test]
    public function it_detects_valid_tokens()
    {
        $validToken = new RefreshToken(
            id: 1,
            user_id: 1,
            token: 'valid_token',
            expiresAt: CarbonImmutable::now()->addHour(),
            revoked: false
        );

        $this->assertTrue($validToken->isValid());

        $revokedToken = new RefreshToken(
            id: 2,
            user_id: 1,
            token: 'revoked_token',
            expiresAt: CarbonImmutable::now()->addHour(),
            revoked: true
        );

        $this->assertFalse($revokedToken->isValid());

        $expiredToken = new RefreshToken(
            id: 3,
            user_id: 1,
            token: 'expired_token',
            expiresAt: CarbonImmutable::now()->subHour(),
            revoked: false
        );

        $this->assertFalse($expiredToken->isValid());

        $revokedExpiredToken = new RefreshToken(
            id: 4,
            user_id: 1,
            token: 'revoked_expired_token',
            expiresAt: CarbonImmutable::now()->subHour(),
            revoked: true
        );

        $this->assertFalse($revokedExpiredToken->isValid());
    }

    #[Test]
    public function it_detects_revoked_tokens()
    {
        $revokedToken = new RefreshToken(
            id: 1,
            user_id: 1,
            token: 'revoked_token',
            expiresAt: CarbonImmutable::now()->addDay(),
            revoked: true
        );

        $this->assertTrue($revokedToken->isRevoked());

        $activeToken = new RefreshToken(
            id: 2,
            user_id: 1,
            token: 'active_token',
            expiresAt: CarbonImmutable::now()->addDay(),
            revoked: false
        );

        $this->assertFalse($activeToken->isRevoked());
    }

    #[Test]
    public function it_has_mutable_revoked_property()
    {
        $refreshToken = new RefreshToken(
            id: 1,
            user_id: 1,
            token: 'test_token',
            expiresAt: CarbonImmutable::now()->addDay(),
            revoked: false
        );

        $refreshToken->revoked = true;

        $this->assertTrue($refreshToken->revoked);
        $this->assertTrue($refreshToken->isRevoked());
        $this->assertFalse($refreshToken->isValid());

        $refreshToken->revoked = false;
        $this->assertFalse($refreshToken->revoked);
    }

    #[Test]
    public function it_accepts_different_token_formats()
    {
        $tokens = [
            'simple_token',
            'refresh_token_123456',
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
            str_repeat('a', 100),
            '1234567890abcdef',
        ];

        foreach ($tokens as $token) {
            $refreshToken = new RefreshToken(
                id: 1,
                user_id: 1,
                token: $token,
                expiresAt: CarbonImmutable::now()->addDay()
            );

            $this->assertEquals($token, $refreshToken->token);
        }
    }

    #[Test]
    public function it_handles_different_expiration_times()
    {
        $now = CarbonImmutable::now();

        $expirationTimes = [
            $now->addMinute(),
            $now->addHour(),
            $now->addDay(),
            $now->addWeek(),
            $now->addMonth(),
            $now->addYear(),
            $now->addYears(10),
        ];

        foreach ($expirationTimes as $expiresAt) {
            $refreshToken = new RefreshToken(
                id: 1,
                user_id: 1,
                token: 'test_token',
                expiresAt: $expiresAt
            );

            $this->assertEquals($expiresAt, $refreshToken->expiresAt);

            if ($expiresAt->isFuture()) {
                $this->assertTrue($refreshToken->isValid());
                $this->assertFalse($refreshToken->isExpired());
            }
        }
    }

    #[Test]
    public function it_handles_past_expiration_times()
    {
        $now = CarbonImmutable::now();

        $pastTimes = [
            $now->subSecond(),
            $now->subMinute(),
            $now->subHour(),
            $now->subDay(),
            CarbonImmutable::createFromDate(2000, 1, 1),
        ];

        foreach ($pastTimes as $expiresAt) {
            $refreshToken = new RefreshToken(
                id: 1,
                user_id: 1,
                token: 'expired_token',
                expiresAt: $expiresAt
            );

            $this->assertTrue($refreshToken->isExpired());
            $this->assertFalse($refreshToken->isValid());
        }
    }

    #[Test]
    public function it_can_be_converted_to_json()
    {
        $expiresAt = CarbonImmutable::createFromDate(2024, 6, 15)->setTime(14, 30, 0);
        $refreshToken = new RefreshToken(
            id: 55,
            user_id: 33,
            token: 'json_token',
            expiresAt: $expiresAt,
            revoked: true
        );

        $json = json_encode($refreshToken);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(55, $decoded['id']);
        $this->assertEquals(33, $decoded['user_id']);
        $this->assertEquals('json_token', $decoded['token']);
        $this->assertDateEquality($expiresAt, $decoded['expiresAt']);
        $this->assertTrue($decoded['revoked']);
    }

    #[Test]
    public function it_provides_carbon_immutable_instance()
    {
        $expiresAt = CarbonImmutable::now();
        $refreshToken = new RefreshToken(
            id: 1,
            user_id: 1,
            token: 'test',
            expiresAt: $expiresAt
        );

        $this->assertInstanceOf(CarbonImmutable::class, $refreshToken->expiresAt);

        $originalTime = $refreshToken->expiresAt->getTimestamp();
        $newTime = $refreshToken->expiresAt->addDay()->getTimestamp();

        $this->assertNotEquals($originalTime, $newTime);
        $this->assertEquals($originalTime, $refreshToken->expiresAt->getTimestamp());
    }

    #[Test]
    public function it_can_check_validity_with_edge_cases()
    {
        $expiresNow = new RefreshToken(
            id: 1,
            user_id: 1,
            token: 'expires_now',
            expiresAt: CarbonImmutable::now()
        );

        $this->assertTrue($expiresNow->isExpired());
        $this->assertFalse($expiresNow->isValid());

        $expiresNextSecond = new RefreshToken(
            id: 2,
            user_id: 1,
            token: 'expires_soon',
            expiresAt: CarbonImmutable::now()->addSecond()
        );

        $this->assertFalse($expiresNextSecond->isExpired());
        $this->assertTrue($expiresNextSecond->isValid());
    }

    #[Test]
    public function it_handles_token_with_revoked_status_changes()
    {
        $refreshToken = new RefreshToken(
            id: 1,
            user_id: 1,
            token: 'changeable_token',
            expiresAt: CarbonImmutable::now()->addDay(),
            revoked: false
        );

        $this->assertTrue($refreshToken->isValid());
        $this->assertFalse($refreshToken->isRevoked());

        $refreshToken->revoked = true;

        $this->assertFalse($refreshToken->isValid());
        $this->assertTrue($refreshToken->isRevoked());

        $refreshToken->revoked = false;

        $this->assertTrue($refreshToken->isValid());
        $this->assertFalse($refreshToken->isRevoked());
    }

    #[Test]
    public function it_can_be_compared_by_properties()
    {
        $expiresAt = CarbonImmutable::now()->addDay();

        $token1 = new RefreshToken(
            id: 1,
            user_id: 100,
            token: 'token123',
            expiresAt: $expiresAt,
            revoked: false
        );

        $token2 = new RefreshToken(
            id: 1,
            user_id: 100,
            token: 'token123',
            expiresAt: $expiresAt,
            revoked: false
        );

        $token3 = new RefreshToken(
            id: 2,
            user_id: 100,
            token: 'token123',
            expiresAt: $expiresAt,
            revoked: false
        );

        $this->assertEquals($token1->id, $token2->id);
        $this->assertEquals($token1->user_id, $token2->user_id);
        $this->assertEquals($token1->token, $token2->token);
        $this->assertEquals($token1->expiresAt, $token2->expiresAt);
        $this->assertEquals($token1->revoked, $token2->revoked);

        $this->assertNotEquals($token1->id, $token3->id);
    }

    #[Test]
    public function it_has_readonly_properties_except_revoked()
    {
        $expiresAt = CarbonImmutable::now();
        $refreshToken = new RefreshToken(
            id: 10,
            user_id: 20,
            token: 'test_token',
            expiresAt: $expiresAt
        );

        $this->assertEquals(10, $refreshToken->id);
        $this->assertEquals(20, $refreshToken->user_id);
        $this->assertEquals('test_token', $refreshToken->token);
        $this->assertEquals($expiresAt, $refreshToken->expiresAt);

        $this->assertFalse($refreshToken->revoked);
        $refreshToken->revoked = true;
        $this->assertTrue($refreshToken->revoked);
    }
}
