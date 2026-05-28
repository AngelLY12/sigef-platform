<?php

namespace Tests\Stubs\Repositories\Query;
use App\Core\Application\DTO\Response\User\UserExtraDataResponse;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Application\DTO\Response\User\UserAuthResponse;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Application\DTO\Response\User\UserRecipientDTO;
use App\Core\Application\DTO\Response\User\UserWithPendingSumamaryResponse;
use App\Models\User as ModelsUser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Generator;

class UserQueryRepStub implements UserQueryRepInterface
{
    private ?User $nextFindUserByEmailResult = null;
    private ?User $nextFindByIdResult = null;
    private User $nextGetUserWithStudentDetailResult;
    private User $nextGetUserByStripeCustomerResult;
    private UserIdListDTO $nextGetUserIdsByControlNumbersResult;
    private UserExtraDataResponse $nextGetUserExtraDataResult;
    private int $nextCountStudentsResult = 0;
    private ?User $nextFindBySearchResult = null;
    private array $nextGetRecipientsResult = [];
    private array $nextGetRecipientsIdsResult = [];
    private array $nextGetRecipientsFromIdsResult = [];
    private bool $nextHasAnyRecipientResult = false;
    private bool $nextHasRoleResult = false;
    private array $nextGetStudentsWithPendingSummaryResult = [];
    private LengthAwarePaginator $nextFindActiveStudentsResult;
    private LengthAwarePaginator $nextFindAllUsersResult;
    private ?UserAuthResponse $nextFindAuthUserResult = null;
    private Collection $nextFindByIdsResult;
    private array $nextFindUserRolesResult = [];
    private ModelsUser $nextFindModelEntityResult;
    private Generator $nextGetUsersByRoleCursorResult;
    private Generator $nextGetUsersByCurpCursorResult;
    private bool $nextUserHasUnreadNotificationsResult = false;

    public function __construct()
    {
        // Valores por defecto
        $this->nextGetUserWithStudentDetailResult = new User(
            curp: 'TEST123456ABCDEFG0',
            name: 'Test',
            last_name: 'User',
            email: 'test@example.com',
            password: 'password',
            phone_number: '1234567890',
            status: UserStatus::ACTIVO,
            id: 1
        );

        $this->nextGetUserByStripeCustomerResult = new User(
            curp: 'STRIPE123456ABCDEF',
            name: 'Stripe',
            last_name: 'Customer',
            email: 'stripe@example.com',
            password: 'password',
            phone_number: '0987654321',
            status: UserStatus::ACTIVO,
            id: 2,
            stripe_customer_id: 'cus_test123'
        );

        $this->nextGetUserIdsByControlNumbersResult = new UserIdListDTO([]);
        $this->nextFindActiveStudentsResult = new LengthAwarePaginator([], 0, 15);
        $this->nextFindAllUsersResult = new LengthAwarePaginator([], 0, 15);
        $this->nextFindByIdsResult = collect();
        $this->nextFindModelEntityResult = new class extends ModelsUser {
            public function __construct(array $attributes = []) {
                parent::__construct($attributes);
            }
        };

        $this->nextGetUsersByRoleCursorResult = $this->createEmptyGenerator();
        $this->nextGetUsersByCurpCursorResult = $this->createEmptyGenerator();
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->nextFindUserByEmailResult;
    }

    public function findById(int $userId): ?User
    {
        return $this->nextFindByIdResult;
    }

    public function getUserWithStudentDetail(int $userId): User
    {
        return $this->nextGetUserWithStudentDetailResult;
    }

    public function getUserByStripeCustomer(string $customerId): User
    {
        return $this->nextGetUserByStripeCustomerResult;
    }

    public function getUserIdsByControlNumbers(array $controlNumbers): UserIdListDTO
    {
        return $this->nextGetUserIdsByControlNumbersResult;
    }

    public function countStudents(bool $onlyThisYear): int
    {
        return $this->nextCountStudentsResult;
    }

    public function findActiveStudents(?string $search, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->nextFindActiveStudentsResult;
    }

    public function findBySearch(string $search): ?User
    {
        return $this->nextFindBySearchResult;
    }

    public function getRecipients(PaymentConcept $concept, string $appliesTo): array
    {
        return $this->nextGetRecipientsResult;
    }

    public function getRecipientsIds(PaymentConcept $concept, string $appliesTo): array
    {
        return $this->nextGetRecipientsIdsResult;
    }

    public function getRecipientsFromIds(array $ids): array
    {
        return $this->nextGetRecipientsFromIdsResult;
    }

    public function hasAnyRecipient(PaymentConcept $concept, string $appliesTo): bool
    {
        return $this->nextHasAnyRecipientResult;
    }

    public function hasRole(int $userId, string $role): bool
    {
        return $this->nextHasRoleResult;
    }

    public function getStudentsWithPendingSummary(array $userIds): array
    {
        return $this->nextGetStudentsWithPendingSummaryResult;
    }

    public function findAllUsers(int $perPage, int $page, ?UserStatus $status = null): LengthAwarePaginator
    {
        return $this->nextFindAllUsersResult;
    }
    public function getExtraUserData(int $userId): UserExtraDataResponse
    {
        return $this->nextGetUserExtraDataResult;
    }

    public function findAuthUser(): ?UserAuthResponse
    {
        return $this->nextFindAuthUserResult;
    }

    public function findByIds(array $ids): Collection
    {
        return $this->nextFindByIdsResult;
    }

    public function findUserRoles(int $userId): array
    {
        return $this->nextFindUserRolesResult;
    }

    public function findModelEntity(int $userId): ModelsUser
    {
        return $this->nextFindModelEntityResult;
    }

    public function getUsersByRoleCursor(string $role): Generator
    {
        foreach ($this->nextGetUsersByRoleCursorResult as $user) {
            yield $user;
        }
    }

    public function getUsersByCurpCursor(array $curps): Generator
    {
        foreach ($this->nextGetUsersByCurpCursorResult as $user) {
            yield $user;
        }
    }

    public function userHasUnreadNotifications(int $userId): bool
    {
        return $this->nextUserHasUnreadNotificationsResult;
    }

    // Métodos de configuración
    public function setNextFindUserByEmailResult(?User $user): self
    {
        $this->nextFindUserByEmailResult = $user;
        return $this;
    }

    public function setNextFindByIdResult(?User $user): self
    {
        $this->nextFindByIdResult = $user;
        return $this;
    }

    public function setNextGetUserWithStudentDetailResult(User $user): self
    {
        $this->nextGetUserWithStudentDetailResult = $user;
        return $this;
    }

    public function setNextGetUserByStripeCustomerResult(User $user): self
    {
        $this->nextGetUserByStripeCustomerResult = $user;
        return $this;
    }

    public function setNextGetUserIdsByControlNumbersResult(UserIdListDTO $dto): self
    {
        $this->nextGetUserIdsByControlNumbersResult = $dto;
        return $this;
    }

    public function setNextCountStudentsResult(int $count): self
    {
        $this->nextCountStudentsResult = $count;
        return $this;
    }

    public function setNextFindBySearchResult(?User $user): self
    {
        $this->nextFindBySearchResult = $user;
        return $this;
    }

    public function setNextFindActiveStudentsResult(LengthAwarePaginator $paginator): self
    {
        $this->nextFindActiveStudentsResult = $paginator;
        return $this;
    }

    public function setNextFindAllUsersResult(LengthAwarePaginator $paginator): self
    {
        $this->nextFindAllUsersResult = $paginator;
        return $this;
    }

    public function setNextGetRecipientsResult(array $recipients): self
    {
        $this->nextGetRecipientsResult = $recipients;
        return $this;
    }

    public function setNextGetRecipientsIdsResult(array $ids): self
    {
        $this->nextGetRecipientsIdsResult = $ids;
        return $this;
    }

    public function setNextGetRecipientsFromIdsResult(array $recipients): self
    {
        $this->nextGetRecipientsFromIdsResult = $recipients;
        return $this;
    }

    public function setNextHasAnyRecipientResult(bool $has): self
    {
        $this->nextHasAnyRecipientResult = $has;
        return $this;
    }

    public function setNextHasRoleResult(bool $has): self
    {
        $this->nextHasRoleResult = $has;
        return $this;
    }

    public function setNextGetStudentsWithPendingSummaryResult(array $summaries): self
    {
        $this->nextGetStudentsWithPendingSummaryResult = $summaries;
        return $this;
    }

    public function setNextFindAuthUserResult(?UserAuthResponse $response): self
    {
        $this->nextFindAuthUserResult = $response;
        return $this;
    }

    public function setNextFindByIdsResult(Collection $users): self
    {
        $this->nextFindByIdsResult = $users;
        return $this;
    }

    public function setNextFindUserRolesResult(array $roles): self
    {
        $this->nextFindUserRolesResult = $roles;
        return $this;
    }

    public function setNextFindModelEntityResult(ModelsUser $model): self
    {
        $this->nextFindModelEntityResult = $model;
        return $this;
    }

    public function setNextGetUsersByRoleCursorResult(Generator $generator): self
    {
        $this->nextGetUsersByRoleCursorResult = $generator;
        return $this;
    }

    public function setNextGetUsersByCurpCursorResult(Generator $generator): self
    {
        $this->nextGetUsersByCurpCursorResult = $generator;
        return $this;
    }

    public function setNextUserHasUnreadNotificationsResult(bool $has): self
    {
        $this->nextUserHasUnreadNotificationsResult = $has;
        return $this;
    }

    private function createEmptyGenerator(): Generator
    {
        return (function () {
            yield from [];
        })();
    }
}
