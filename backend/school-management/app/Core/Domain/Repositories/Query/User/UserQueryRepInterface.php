<?php

namespace App\Core\Domain\Repositories\Query\User;

use App\Core\Application\DTO\Response\User\UserAuthResponse;
use App\Core\Application\DTO\Response\User\UserExtraDataResponse;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Application\DTO\Response\User\UsersAdminSummary;
use App\Core\Application\DTO\Response\User\UsersFinancialSummary;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserStatus;
use App\Models\User as ModelsUser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserQueryRepInterface{
    public function findUserByEmail(string $email):?User;
    public function findById(int $userId): ?User;
    public function getUserWithStudentDetail(int $userId):User;
    public function getUserByStripeCustomer(string $customerId): User;
    public function getUserIdsByControlNumbers(array $controlNumbers): UserIdListDTO;
    public function getControlNumbersBySearch(string $search, int $limit = 15): array;
    public function getUsersPopulationSummary(bool $onlyThisYear): UsersFinancialSummary;
    public function getUsersAdminSummary(bool $onlyThisYear): UsersAdminSummary;
    public function findActiveStudents(?string $search, int $perPage, int $page): LengthAwarePaginator;
    public function findBySearch(string $search): ?User;
    public function getRecipients(PaymentConcept $concept, string $appliesTo): array;
    public function getRecipientsIds(PaymentConcept $concept, string $appliesTo): array;
    public function getRecipientsFromIds(array $ids): array;
    public function hasAnyRecipient(PaymentConcept $concept, string $appliesTo): bool;
    public function hasRole(int $userId, string $role):bool;
    public function getStudentsWithPendingSummary(array $userIds): array;
    public function findAllUsers(int $perPage, int $page, ?UserStatus $status = null): LengthAwarePaginator;
    public function getExtraUserData(int $userId): UserExtraDataResponse;
    public function findAuthUser(): ?UserAuthResponse;
    public function findByIds(array $ids): Collection;
    public function findUserRoles(int $userId): array;
    public function findModelEntity(int $userId): ModelsUser;
    public function getUsersByRoleCursor(string $role): \Generator;
    public function getUsersByCurpCursor(array $curps): \Generator;
    public function userHasUnreadNotifications(int $userId): bool;

}
