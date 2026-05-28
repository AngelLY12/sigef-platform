<?php

namespace Tests\Unit\Domain\Entities;

use Carbon\Carbon;
use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\ParentInvite;
use PHPUnit\Framework\Attributes\Test;

class ParentInviteTest extends BaseDomainTestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $parentInvite = new ParentInvite(
            studentId: 100,
            email: 'parent@example.com',
            token: 'invite_token_123',
            expiresAt: Carbon::now()->addDays(7),
            createdBy: 50
        );

        $this->assertInstanceOf(ParentInvite::class, $parentInvite);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_parameters()
    {
        $expiresAt = Carbon::now()->addDays(14);
        $usedAt = Carbon::now()->addDays(2);

        $parentInvite = new ParentInvite(
            studentId: 200,
            email: 'parent.all@example.com',
            token: 'full_token_abc',
            expiresAt: $expiresAt,
            createdBy: 75,
            id: 10,
            usedAt: $usedAt
        );

        $this->assertInstanceOf(ParentInvite::class, $parentInvite);
        $this->assertEquals(200, $parentInvite->studentId);
        $this->assertEquals('parent.all@example.com', $parentInvite->email);
        $this->assertEquals('full_token_abc', $parentInvite->token);
        $this->assertEquals($expiresAt, $parentInvite->expiresAt);
        $this->assertEquals(75, $parentInvite->createdBy);
        $this->assertEquals(10, $parentInvite->id);
        $this->assertEquals($usedAt, $parentInvite->usedAt);
    }

    #[Test]
    public function it_has_required_attributes()
    {
        $expiresAt = Carbon::now()->addDay();

        $parentInvite = new ParentInvite(
            studentId: 300,
            email: 'required@test.com',
            token: 'required_token',
            expiresAt: $expiresAt,
            createdBy: 80
        );

        $this->assertEquals(300, $parentInvite->studentId);
        $this->assertEquals('required@test.com', $parentInvite->email);
        $this->assertEquals('required_token', $parentInvite->token);
        $this->assertEquals($expiresAt, $parentInvite->expiresAt);
        $this->assertEquals(80, $parentInvite->createdBy);
        $this->assertNull($parentInvite->id);
        $this->assertNull($parentInvite->usedAt);
    }

    #[Test]
    public function it_accepts_valid_data()
    {
        $expiresAt = Carbon::create(2024, 12, 31, 23, 59, 59);

        $parentInvite = new ParentInvite(
            studentId: 150,
            email: 'valid.parent@school.edu',
            token: 'valid_invite_token',
            expiresAt: $expiresAt,
            createdBy: 25,
            id: 5,
            usedAt: null
        );

        $this->assertInstanceOf(ParentInvite::class, $parentInvite);
        $this->assertEquals(150, $parentInvite->studentId);
        $this->assertEquals('valid.parent@school.edu', $parentInvite->email);
        $this->assertEquals('valid_invite_token', $parentInvite->token);
        $this->assertEquals($expiresAt, $parentInvite->expiresAt);
        $this->assertEquals(25, $parentInvite->createdBy);
        $this->assertEquals(5, $parentInvite->id);
        $this->assertNull($parentInvite->usedAt);
    }

    #[Test]
    public function it_detects_expired_invites()
    {
        $expiredInvite = new ParentInvite(
            studentId: 1,
            email: 'expired@test.com',
            token: 'expired_token',
            expiresAt: Carbon::now()->subDay(),
            createdBy: 1
        );

        $this->assertTrue($expiredInvite->isExpired());

        $validInvite = new ParentInvite(
            studentId: 1,
            email: 'valid@test.com',
            token: 'valid_token',
            expiresAt: Carbon::now()->addDay(),
            createdBy: 1
        );

        $this->assertFalse($validInvite->isExpired());

        $expiresNow = new ParentInvite(
            studentId: 1,
            email: 'now@test.com',
            token: 'now_token',
            expiresAt: Carbon::now(),
            createdBy: 1
        );

        $this->assertTrue($expiresNow->isExpired());
    }

    #[Test]
    public function it_detects_used_invites()
    {
        $usedInvite = new ParentInvite(
            studentId: 1,
            email: 'used@test.com',
            token: 'used_token',
            expiresAt: Carbon::now()->addDay(),
            createdBy: 1,
            usedAt: Carbon::now()
        );

        $this->assertTrue($usedInvite->isUsed());

        $unusedInvite = new ParentInvite(
            studentId: 1,
            email: 'unused@test.com',
            token: 'unused_token',
            expiresAt: Carbon::now()->addDay(),
            createdBy: 1
        );

        $this->assertFalse($unusedInvite->isUsed());

        $explicitNull = new ParentInvite(
            studentId: 1,
            email: 'null@test.com',
            token: 'null_token',
            expiresAt: Carbon::now()->addDay(),
            createdBy: 1,
            usedAt: null
        );

        $this->assertFalse($explicitNull->isUsed());
    }

    #[Test]
    public function it_handles_different_email_formats()
    {
        $emails = [
            'parent@example.com',
            'parent.name@school.edu.mx',
            'parent_name+tag@example.com',
            'parent123@test.co.uk',
            'first.last@domain.com',
            'parent@sub.domain.com',
        ];

        foreach ($emails as $email) {
            $parentInvite = new ParentInvite(
                studentId: 1,
                email: $email,
                token: 'test_token',
                expiresAt: Carbon::now()->addDay(),
                createdBy: 1
            );

            $this->assertEquals($email, $parentInvite->email);
        }
    }

    #[Test]
    public function it_handles_different_token_formats()
    {
        $tokens = [
            'simple_token',
            'invite_token_123456',
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
            str_repeat('a', 100),
            '1234567890abcdef',
            'token-with-dashes',
            'token_with_underscores',
        ];

        foreach ($tokens as $token) {
            $parentInvite = new ParentInvite(
                studentId: 1,
                email: 'test@example.com',
                token: $token,
                expiresAt: Carbon::now()->addDay(),
                createdBy: 1
            );

            $this->assertEquals($token, $parentInvite->token);
        }
    }

    #[Test]
    public function it_handles_different_expiration_times()
    {
        $now = Carbon::now();

        $expirationTimes = [
            $now->addMinute(),
            $now->addHour(),
            $now->addDay(),
            $now->addWeek(),
            $now->addMonth(),
            $now->addYear(),
            Carbon::create(2025, 12, 31, 23, 59, 59), // Fecha específica
        ];

        foreach ($expirationTimes as $expiresAt) {
            $parentInvite = new ParentInvite(
                studentId: 1,
                email: 'test@example.com',
                token: 'test_token',
                expiresAt: $expiresAt,
                createdBy: 1
            );

            $this->assertEquals($expiresAt, $parentInvite->expiresAt);

            $shouldBeExpired = $expiresAt->isPast();
            $this->assertEquals($shouldBeExpired, $parentInvite->isExpired());
        }
    }

    #[Test]
    public function it_accepts_different_created_by_users()
    {
        $creators = [1, 100, 999, 1000, 5000];

        foreach ($creators as $creator) {
            $parentInvite = new ParentInvite(
                studentId: 1,
                email: 'test@example.com',
                token: 'test_token',
                expiresAt: Carbon::now()->addDay(),
                createdBy: $creator
            );

            $this->assertEquals($creator, $parentInvite->createdBy);
        }
    }

    #[Test]
    public function it_can_be_converted_to_json()
    {
        $expiresAt = Carbon::create(2024, 7, 1, 0, 0, 0);

        $parentInvite = new ParentInvite(
            studentId: 300,
            email: 'json@test.com',
            token: 'json_token_123',
            expiresAt: $expiresAt,
            createdBy: 50,
            id: 20
        );

        $json = json_encode($parentInvite);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(20, $decoded['id']);
        $this->assertEquals(300, $decoded['studentId']);
        $this->assertEquals('json@test.com', $decoded['email']);
        $this->assertEquals('json_token_123', $decoded['token']);
        $this->assertDateEquality($expiresAt->toImmutable(), $decoded['expiresAt']);
        $this->assertEquals(50, $decoded['createdBy']);
        $this->assertNull($decoded['usedAt']);
    }

    #[Test]
    public function it_handles_json_with_null_values()
    {
        $parentInvite = new ParentInvite(
            studentId: 400,
            email: 'null.values@test.com',
            token: 'null_token',
            expiresAt: Carbon::now()->addDay(),
            createdBy: 75
        );

        $json = json_encode($parentInvite);
        $decoded = json_decode($json, true);

        $this->assertJson($json);
        $this->assertEquals(400, $decoded['studentId']);
        $this->assertEquals('null.values@test.com', $decoded['email']);
        $this->assertEquals('null_token', $decoded['token']);
        $this->assertNotNull($decoded['expiresAt']);
        $this->assertEquals(75, $decoded['createdBy']);
        $this->assertNull($decoded['id']);
        $this->assertNull($decoded['usedAt']);
    }

    #[Test]
    public function it_detects_used_and_expired_combination()
    {
        $usedExpired = new ParentInvite(
            studentId: 1,
            email: 'used.expired@test.com',
            token: 'token1',
            expiresAt: Carbon::now()->subDay(),
            createdBy: 1,
            usedAt: Carbon::now()->subHour()
        );

        $this->assertTrue($usedExpired->isUsed());
        $this->assertTrue($usedExpired->isExpired());

        $usedValid = new ParentInvite(
            studentId: 1,
            email: 'used.valid@test.com',
            token: 'token2',
            expiresAt: Carbon::now()->addDay(),
            createdBy: 1,
            usedAt: Carbon::now()
        );

        $this->assertTrue($usedValid->isUsed());
        $this->assertFalse($usedValid->isExpired());

        $unusedExpired = new ParentInvite(
            studentId: 1,
            email: 'unused.expired@test.com',
            token: 'token3',
            expiresAt: Carbon::now()->subDay(),
            createdBy: 1
        );

        $this->assertFalse($unusedExpired->isUsed());
        $this->assertTrue($unusedExpired->isExpired());

        $unusedValid = new ParentInvite(
            studentId: 1,
            email: 'unused.valid@test.com',
            token: 'token4',
            expiresAt: Carbon::now()->addDay(),
            createdBy: 1
        );

        $this->assertFalse($unusedValid->isUsed());
        $this->assertFalse($unusedValid->isExpired());
    }

    #[Test]
    public function it_handles_edge_cases_for_dates()
    {
        $farFuture = Carbon::now()->addYears(100);
        $farFutureInvite = new ParentInvite(
            studentId: 1,
            email: 'future@test.com',
            token: 'future_token',
            expiresAt: $farFuture,
            createdBy: 1
        );
        $this->assertFalse($farFutureInvite->isExpired());

        $farPast = Carbon::now()->subYears(100);
        $farPastInvite = new ParentInvite(
            studentId: 1,
            email: 'past@test.com',
            token: 'past_token',
            expiresAt: $farPast,
            createdBy: 1
        );
        $this->assertTrue($farPastInvite->isExpired());

        $expiresAt = Carbon::now()->addWeek();
        $usedAt = Carbon::now()->subWeek();
        $invite = new ParentInvite(
            studentId: 1,
            email: 'dates@test.com',
            token: 'dates_token',
            expiresAt: $expiresAt,
            createdBy: 1,
            usedAt: $usedAt
        );
        $this->assertTrue($invite->isUsed());
        $this->assertFalse($invite->isExpired());
    }

    #[Test]
    public function it_can_be_compared_by_properties()
    {
        $expiresAt = Carbon::now()->addDay();

        $invite1 = new ParentInvite(
            studentId: 100,
            email: 'test1@example.com',
            token: 'token1',
            expiresAt: $expiresAt,
            createdBy: 50
        );

        $invite2 = new ParentInvite(
            studentId: 100,
            email: 'test1@example.com',
            token: 'token1',
            expiresAt: $expiresAt,
            createdBy: 50
        );

        $invite3 = new ParentInvite(
            studentId: 101,
            email: 'test1@example.com',
            token: 'token1',
            expiresAt: $expiresAt,
            createdBy: 50
        );

        $invite4 = new ParentInvite(
            studentId: 100,
            email: 'test2@example.com',
            token: 'token1',
            expiresAt: $expiresAt,
            createdBy: 50
        );

        $this->assertEquals($invite1->studentId, $invite2->studentId);
        $this->assertEquals($invite1->email, $invite2->email);
        $this->assertEquals($invite1->token, $invite2->token);
        $this->assertEquals($invite1->expiresAt, $invite2->expiresAt);
        $this->assertEquals($invite1->createdBy, $invite2->createdBy);

        $this->assertNotEquals($invite1->studentId, $invite3->studentId);
        $this->assertNotEquals($invite1->email, $invite4->email);
    }

    #[Test]
    public function it_provides_consistent_string_representation()
    {
        $expiresAt = Carbon::create(2024, 6, 20, 10, 0, 0);

        $parentInvite = new ParentInvite(
            studentId: 500,
            email: 'represent@test.com',
            token: 'repr_token',
            expiresAt: $expiresAt,
            createdBy: 30,
            id: 25
        );

        $string = "ParentInvite[id:25, student:500, email:represent@test.com]";

        $jsonString = json_encode($parentInvite);
        $this->assertStringContainsString('"id":25', $jsonString);
        $this->assertStringContainsString('"studentId":500', $jsonString);
        $this->assertStringContainsString('"email":"represent@test.com"', $jsonString);
        $this->assertStringContainsString('"token":"repr_token"', $jsonString);
    }

    #[Test]
    public function it_can_be_used_in_collections()
    {
        $invites = [
            new ParentInvite(studentId: 1, email: 'inv1@test.com', token: 't1', expiresAt: Carbon::now()->addDay(), createdBy: 1),
            new ParentInvite(studentId: 2, email: 'inv2@test.com', token: 't2', expiresAt: Carbon::now()->addDay(), createdBy: 1),
            new ParentInvite(studentId: 3, email: 'inv3@test.com', token: 't3', expiresAt: Carbon::now()->addDay(), createdBy: 1),
            new ParentInvite(studentId: 4, email: 'inv4@test.com', token: 't4', expiresAt: Carbon::now()->addDay(), createdBy: 1, usedAt: Carbon::now()),
        ];

        $this->assertCount(4, $invites);
        $this->assertInstanceOf(ParentInvite::class, $invites[0]);
        $this->assertInstanceOf(ParentInvite::class, $invites[1]);
        $this->assertInstanceOf(ParentInvite::class, $invites[2]);
        $this->assertInstanceOf(ParentInvite::class, $invites[3]);

        $this->assertEquals('inv1@test.com', $invites[0]->email);
        $this->assertEquals('inv2@test.com', $invites[1]->email);
        $this->assertEquals('inv3@test.com', $invites[2]->email);
        $this->assertEquals('inv4@test.com', $invites[3]->email);
        $this->assertTrue($invites[3]->isUsed());
    }
}
