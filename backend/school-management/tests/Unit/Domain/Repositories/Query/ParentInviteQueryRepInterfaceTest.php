<?php

namespace Tests\Unit\Domain\Repositories\Query;

use Tests\Stubs\Repositories\Query\ParentInviteQueryRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Query\Misc\ParentInviteQueryRepInterface;
use App\Core\Domain\Entities\ParentInvite;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class ParentInviteQueryRepInterfaceTest extends BaseRepositoryTestCase
{
    protected string $interfaceClass = ParentInviteQueryRepInterface::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ParentInviteQueryRepStub();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository);
        $this->assertImplementsInterface($this->interfaceClass);
    }

    #[Test]
    public function it_has_required_findByToken_method(): void
    {
        $this->assertMethodExists('findByToken');
    }

    #[Test]
    public function findByToken_returns_invite_when_found(): void
    {
        $expiresAt = Carbon::now()->addDays(7);
        $invite = new ParentInvite(
            studentId: 1,
            email: 'parent@example.com',
            token: 'abc-123',
            expiresAt: $expiresAt,
            createdBy: 2,
            id: 1
        );

        $this->repository->setNextFindByTokenResult($invite);

        $result = $this->repository->findByToken('abc-123');

        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertEquals('abc-123', $result->token);
        $this->assertEquals(1, $result->studentId);
        $this->assertEquals('parent@example.com', $result->email);
        $this->assertFalse($result->isExpired());
        $this->assertFalse($result->isUsed());
    }

    #[Test]
    public function findByToken_returns_null_when_not_found(): void
    {
        $this->repository->setNextFindByTokenResult(null);

        $result = $this->repository->findByToken('no-existe');

        $this->assertNull($result);
    }

    #[Test]
    public function findByToken_with_expired_invite(): void
    {
        $expiresAt = Carbon::now()->subDay();
        $invite = new ParentInvite(
            studentId: 1,
            email: 'parent@example.com',
            token: 'expirado',
            expiresAt: $expiresAt,
            createdBy: 2,
            id: 2
        );

        $this->repository->setNextFindByTokenResult($invite);

        $result = $this->repository->findByToken('expirado');

        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertTrue($result->isExpired());
    }

    #[Test]
    public function findByToken_with_used_invite(): void
    {
        $expiresAt = Carbon::now()->addDays(7);
        $usedAt = Carbon::now()->subHours(2);
        $invite = new ParentInvite(
            studentId: 1,
            email: 'parent@example.com',
            token: 'usado',
            expiresAt: $expiresAt,
            createdBy: 2,
            id: 3,
            usedAt: $usedAt
        );

        $this->repository->setNextFindByTokenResult($invite);

        $result = $this->repository->findByToken('usado');

        $this->assertInstanceOf(ParentInvite::class, $result);
        $this->assertTrue($result->isUsed());
        $this->assertFalse($result->isExpired());
    }

    #[Test]
    public function method_has_correct_signature(): void
    {
        $this->assertMethodParameterType('findByToken', 'string');
        $this->assertMethodParameterCount('findByToken', 1);
        $this->assertMethodReturnType('findByToken', ParentInvite::class);
    }
}
