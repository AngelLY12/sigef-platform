<?php

namespace App\Core\Application\Services\Parents;

use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Parents\AcceptParentInvitationUseCase;
use App\Core\Application\UseCases\Parents\DeleteParentStudentRelationUseCase;
use App\Core\Application\UseCases\Parents\GetParentChildrenUseCase;
use App\Core\Application\UseCases\Parents\GetStudentParentsUseCase;
use App\Core\Application\UseCases\Parents\SendParentInviteUseCase;
use App\Core\Domain\Entities\ParentInvite;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\AdminCacheSufix;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\ParentCacheSufix;
use App\Core\Infraestructure\Cache\CacheService;

class ParentsServiceFacades
{
    use HasCache;
    private const TAG_PARENT_CHILDREN = [CachePrefix::PARENT->value, ParentCacheSufix::CHILDREN->value];
    private const TAG_STUDENT_PARENTS = [CachePrefix::STUDENT->value, ParentCacheSufix::PARENTS->value];
    public function __construct(
        private SendParentInviteUseCase $send,
        private AcceptParentInvitationUseCase $accept,
        private GetParentChildrenUseCase $children,
        private GetStudentParentsUseCase $parents,
        private DeleteParentStudentRelationUseCase $deleteRelation,
        private CacheService $service
    )
    {
        $this->setCacheService($service);
    }

    public function sendInvitation(int $studentId, string $parentEmail, int $createdBy): ParentInvite
    {
        return $this->idempotent(
            'parent_invite_send',
            [
                'student_id' => $studentId,
                'parent_email' => $parentEmail,
                'created_by' => $createdBy,
            ],
            function () use ($studentId, $parentEmail, $createdBy) {
                return $this->send->execute($studentId,$parentEmail,$createdBy);
            }
        );
    }

    public function acceptInvitation(string $token, ?string $relationship=null): void
    {
        $this->idempotent(
            'parent_invite_accept',
            ['token' => $token],
            function () use ($token, $relationship) {
                $this->accept->execute($token, $relationship);
                return true;
            },
            600
        );
    }

    public function getParentChildren(User $parent, bool $forceRefresh): ParentChildrenResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::PARENT->value,
            ParentCacheSufix::CHILDREN->value,
            ['parentId' => $parent->id]
        );
        $tags = array_merge(self::TAG_PARENT_CHILDREN, ["parent:{$parent->id}"]);

        return $this->mediumCache($key, fn() => $this->children->execute($parent), $tags, $forceRefresh);
    }

    public function getStudentParents(User $student, bool $forceRefresh): StudentParentsResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            ParentCacheSufix::PARENTS->value,
            ['studentId' => $student->id]
        );

        $tags = array_merge(self::TAG_STUDENT_PARENTS, ["student:{$student->id}"]);
        return $this->mediumCache($key, fn() => $this->parents->execute($student),$tags ,$forceRefresh);
    }

    public function deleteParentStudentRelation(int $parentId, int $studentId): bool
    {
        return $this->idempotent(
            'parent_student_delete',
            [
                'parent_id' => $parentId,
                'student_id' => $studentId
            ],
            function () use ($parentId, $studentId) {
                $result=$this->deleteRelation->execute($parentId, $studentId);
                $this->service->flushTags(array_merge(self::TAG_PARENT_CHILDREN, ["parent:{$parentId}"]));
                $this->service->flushTags(array_merge(self::TAG_STUDENT_PARENTS, ["student:{$studentId}"]));
                return $result;
            }
        );
    }

}
