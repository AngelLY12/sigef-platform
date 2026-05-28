<?php

namespace App\Core\Application\UseCases\Parents;

use App\Core\Application\Mappers\ParentStudentMapper;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\ParentCacheSufix;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Repositories\Command\Misc\ParentInviteRepInterface;
use App\Core\Domain\Repositories\Command\User\ParentStudentRepInterface;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Domain\Repositories\Query\Misc\ParentInviteQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Events\ParentInvitationAccepted;
use App\Events\ParentInvitationFailed;
use App\Exceptions\NotAllowed\InvalidInvitationException;
use App\Exceptions\NotFound\UserNotFoundException;
use Illuminate\Support\Facades\DB;

class AcceptParentInvitationUseCase
{
    private const TAG_PARENT_CHILDREN = [CachePrefix::PARENT->value, ParentCacheSufix::CHILDREN->value];
    private const TAG_STUDENT_PARENTS = [CachePrefix::STUDENT->value, ParentCacheSufix::PARENTS->value];
   public function __construct(
        private ParentInviteQueryRepInterface $inviteQRepo,
        private ParentInviteRepInterface $inviteRepo,
        private ParentStudentRepInterface $parentRepo,
        private UserQueryRepInterface $userQRepo,
        private UserRepInterface $userRepo,
        private CacheService $service
    ) {}
    public function execute(string $token, ?string $relationship=null): void
    {
        $inv = $this->inviteQRepo->findByToken($token);

        if (!$inv || $inv->isUsed() || $inv->isExpired()) {
            throw new InvalidInvitationException();
        }

        $student=$this->userQRepo->findById($inv->studentId);
        $parent=$this->userQRepo->findUserByEmail($inv->email);
        if(!$student || !$parent)
        {
            throw new UserNotFoundException();
        }

        try{
            DB::beginTransaction();
            if (!$parent->isParent()) {
                $this->userRepo->assignRole($parent->id, UserRoles::PARENT->value);
                $parent = $this->userQRepo->findById($parent->id);
            }
            $parentRole = $parent->getRole(UserRoles::PARENT->value);
            $studentRole = $student->getRole(UserRoles::STUDENT->value);

            $data=[
                'parentId' => $parent->id,
                'studentId' => $student->id,
                'parentRoleId' => $parentRole->id,
                'studentRoleId' => $studentRole->id,
                'relationship' => $relationship ?? null
            ];

            $this->parentRepo->create(ParentStudentMapper::toDomain($data));

            $this->inviteRepo->markAsUsed($inv->id);
            $this->service->flushTags(array_merge(self::TAG_PARENT_CHILDREN, ["parent:{$parent->id}"]));
            $this->service->flushTags(array_merge(self::TAG_STUDENT_PARENTS, ["student:{$student->id}"]));
            DB::commit();

            event(new ParentInvitationAccepted(
                $student->id,
                $parent->fullName(),
                $student->fullName()
            ));

        }catch (\Exception $exception){
            DB::rollBack();
            event(new ParentInvitationFailed(
                $student->id,
                $parent->fullName(),
                $student->fullName()
            ));
            throw $exception;
        }
    }

}
