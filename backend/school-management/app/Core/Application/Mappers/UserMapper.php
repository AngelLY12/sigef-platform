<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\DTO\Request\User\UpdateUserPermissionsDTO;
use App\Core\Application\DTO\Request\User\UpdateUserRoleDTO;
use App\Core\Application\DTO\Response\StudentDetail\StudentDetailDTO;
use App\Core\Application\DTO\Response\User\PromotedStudentsResponse;
use App\Core\Application\DTO\Response\User\UserAuthResponse;
use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Application\DTO\Response\User\UserDataResponse;
use App\Core\Application\DTO\Response\User\UserExtraDataResponse;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Application\DTO\Response\User\UserListItemResponse;
use App\Core\Application\DTO\Response\User\UserRecipientDTO;
use App\Core\Application\DTO\Response\User\UsersAdminSummary;
use App\Core\Application\DTO\Response\User\UsersFinancialSummary;
use App\Core\Application\DTO\Response\User\UserWithPaymentResponse;
use App\Core\Application\DTO\Response\User\UserWithPendingSumamaryResponse;
use App\Core\Application\DTO\Response\User\UserWithStudentDetailResponse;
use App\Core\Application\DTO\Response\User\UserWithUpdatedPermissionsResponse;
use App\Core\Application\DTO\Response\User\UserWithUpdatedRoleResponse;
use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Utils\Helpers\DateHelper;
use App\Models\User;
use App\Models\User as EloquentUser;
use App\Core\Domain\Entities\User as DomainUser;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserStatus;
use Carbon\Carbon;

class UserMapper{

    public static function toDomain(CreateUserDTO $user): DomainUser
    {
        return new DomainUser(
            curp: $user->curp,
            name: $user->name,
            last_name: $user->last_name,
            email: $user->email,
            password: $user->password,
            phone_number: $user->phone_number,
            status: $user->status ?? null,
            registration_date: $user->registration_date ?? null,
            birthdate: $user->birthdate ?? null,
            gender: $user->gender ?? null,
            address: $user->address ?? [],
            blood_type: $user->blood_type ?? null,
            stripe_customer_id: null
        );

    }

    public static function toUserAuthResponse(User $user): UserAuthResponse
    {
        return new UserAuthResponse(
            id: $user->id,
            curp: $user->curp,
            name: $user->name,
            last_name: $user->last_name,
            email: $user->email,
            phone_number: $user->phone_number,
            status: $user->status->value,
            registration_date: $user->registration_date?->toDateString(),
            emailVerifiedAt: $user->email_verified_at?->toDateString(),
            birthdate: $user->birthdate?->toDateString(),
            gender: $user->gender?->value,
            address:$user->address,
            blood_type: $user->blood_type?->value,
            stripe_customer_id: $user->stripe_customer_id,
        );
    }

    public static function toCreateUserDTO(array $data): CreateUserDTO
    {
        return new CreateUserDTO(
            name: $data['name'],
            last_name: $data['last_name'],
            email: $data['email'],
            password: $data['password'],
            phone_number: $data['phone_number'],
            curp: $data['curp'],
            birthdate: isset($data['birthdate']) ? new Carbon($data['birthdate']) : null,
            gender: isset($data['gender'])
            ? UserGender::from(strtolower($data['gender']))
            : null,
            address: isset($data['address']) ? $data['address'] : null,
            blood_type: isset($data['blood_type'])
           ? UserBloodType::from($data['blood_type'])
           : null,
            registration_date: isset($data['registration_date']) ? new Carbon($data['registration_date']) : Carbon::now(),
            status: isset($data['status']) ? UserStatus::from($data['status']) : UserStatus::ACTIVO
        );

    }

    public static function toDataResponse(DomainUser $user): UserDataResponse{
        return new UserDataResponse(
            id: $user->id ?? null,
            fullName: $user->fullName() ?? null,
            email: $user->email ?? null,
            curp: $user->curp ?? null,
            n_control: $user->studentDetail->n_control ?? null,
        );
    }

    public static function toUserWithPaymentResponse(DomainUser $student,$concept): UserWithPaymentResponse
    {
        return new UserWithPaymentResponse(
            id: $student->id ?? null,
            fullName: $student->fullName() ?? null,
            concept: $concept->concept_name ?? null,
            amount: $concept->amount ?? null
        );
    }

    public static function toRecipientDTO(array $user): UserRecipientDTO
    {
        return new UserRecipientDTO(
            id: $user['id'] ?? null,
            fullName: $user['name'] . ' ' . $user['last_name'] ?? null,
            email: $user['email'] ?? null
        );
    }

    public static function toUserWithStudentDetailResponse(EloquentUser $user): UserWithStudentDetailResponse
    {
        return new UserWithStudentDetailResponse(
            id: $user->id ?? null,
            name: $user->name ?? null,
            last_name: $user->last_name ?? null,
            email: $user->email ?? null,
            phone_number: $user->phone_number ?? null,
            birthdate: $user->birthdate ? $user->birthdate->format('Y-m-d H:i:s'): null,
            gender: $user->gender->value ?? null,
            curp: $user->curp ?? null,
            address: $user->address ?? null,
            stripe_customer_id: $user->stripe_customer_id ?? null,
            blood_type: $user->blood_type->value ?? null,
            registration_date: $user->registration_date ? $user->registration_date->format('Y-m-d H:i:s'): null,
            status: $user->status->value ?? null,
            career_id: $user->studentDetail?->career_id ?? null,
            semestre: $user->studentDetail?->semestre ?? null,
            group: $user->studentDetail?->group ?? null,
            workshop: $user->studentDetail?->workshop ?? null,
            n_control: $user->studentDetail?->n_control ?? null,
        );
    }

    public static function toUserIdListDTO(array $ids): UserIdListDTO
    {
        return new UserIdListDTO(
            userIds:$ids ?? null
        );
    }

    public static function toUserWithPendingSummaryResponse(array $studentSummary): UserWithPendingSumamaryResponse
    {
        return new UserWithPendingSumamaryResponse(
            userId: $studentSummary['user_id'] ?? null,
            fullName: $studentSummary['name'] ?? null,
            n_control: $studentSummary['n_control'] ?? null,
            semestre: $studentSummary['semestre'] ?? null,
            career_name: $studentSummary['career'] ?? null,
            num_pending: $studentSummary['total_count'] ?? null,
            num_expired: $studentSummary['expired_count'] ?? null,
            total_amount_pending: $studentSummary['total_amount'] ?? null,
            total_paid: $studentSummary['total_paid'] ?? null,
            expired_amount: $studentSummary['expired_amount'] ?? null,
            num_paid: $studentSummary['total_paid_concepts'] ?? null,
        );
    }

    public static function toUpdateUserPermissionsDTO(array $data): UpdateUserPermissionsDTO
    {
        return new UpdateUserPermissionsDTO(
            curps: $data['curps'] ?? [],
            role: $data['role'] ?? null,
            permissionsToAdd: $data['permissionsToAdd'] ?? [],
            permissionsToRemove: $data['permissionsToRemove'] ?? [],

        );
    }

    public static function toUserUpdatedPermissionsResponse(array $summary,array $usersProcessed, array $updatedPermissions ): UserWithUpdatedPermissionsResponse
    {
        return new UserWithUpdatedPermissionsResponse(
            summary: $summary,
            users:$usersProcessed,
            permissionsProcessed: $updatedPermissions,
        );
    }
    public static function toUpdateUserRoleDTO(array $data): UpdateUserRoleDTO
    {
        return new UpdateUserRoleDTO(
            curps:$data['curps'] ?? [],
            rolesToAdd:$data['rolesToAdd'] ?? [],
            rolesToRemove:$data['rolesToRemove'] ?? []
        );
    }

    public static function toUserWithUptadedRoleResponse(array $summary, array $usersProcessed, array $updatedRoles): UserWithUpdatedRoleResponse
    {
        return new UserWithUpdatedRoleResponse(
            summary: $summary,
            users: $usersProcessed,
            rolesProcessed:$updatedRoles,
        );
    }

    public static function toUserChangedStatusResponse(array $data): UserChangedStatusResponse
    {
        return new UserChangedStatusResponse(
            newStatus: $data['status'],
            totalUpdated: $data['total'] ?? 0
        );
    }

    public static function toPromotedStudentsResponse(array $data): PromotedStudentsResponse
    {
        return new PromotedStudentsResponse(
            promotedStudents: $data['promotedStudents'] ?? 0,
            desactivatedStudents: $data['desactivatedStudents'] ?? 0,
        );
    }

    public static function toUserExtrDataResponse(EloquentUser $user): UserExtraDataResponse
    {
        $studentDetail = null;

        if ($user->studentDetail) {
            $studentDetail = new StudentDetailDTO(
                nControl: $user->studentDetail->n_control ?? null,
                semestre: $user->studentDetail->semestre ?? null,
                group: $user->studentDetail->group ?? null,
                workshop: $user->studentDetail->workshop ?? null,
                careerName: $user->studentDetail->career?->career_name,
            );
        }
        return new UserExtraDataResponse(
            userId: $user->id,
            basicInfo:[
                'phone_number' => $user->phone_number,
                'birthdate' => $user->birthdate?->format('Y-m-d'),
                'age' => $user->birthdate?->age,
                'address' => $user->address ?? [],
                'blood_type' => $user->blood_type?->value,
                'registration_date' => $user->registration_date->format('Y-m-d'),
            ],
            roles: $user->roles->pluck('name')->toArray(),
            permissions: $user->permissions->pluck('name')->toArray(),
            studentDetail: $studentDetail
        );
    }

    public static function toUserListItemResponse(EloquentUser $user): UserListItemResponse
    {
        return new UserListItemResponse(
            id: $user->id,
            fullName: $user->name . ' ' . $user->last_name,
            email: $user->email,
            curp: $user->curp,
            status: $user->status->value,
            roles_count: (int) $user->roles_count,
            createdAtHuman: $user->created_at->diffForHumans(),
            deletedAtHuman: $user->mark_as_deleted_at ? DateHelper::daysUntilDeletion($user->mark_as_deleted_at) : null,
        );
    }

    public static function toUsersFinancialSummary(int $totalStudents, int $totalApplicants): UsersFinancialSummary
    {
        return new UsersFinancialSummary(
            totalStudents: $totalStudents,
            totalApplicants: $totalApplicants,
        );
    }

}
