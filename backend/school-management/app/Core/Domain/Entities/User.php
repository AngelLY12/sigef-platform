<?php

namespace App\Core\Domain\Entities;

use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="DomainUser",
 *     type="object",
 *     description="Representa un usuario del sistema",
 *     @OA\Property(property="curp", type="string", example="PEMJ950615HDFRZN09"),
 *     @OA\Property(property="name", type="string", example="Juan"),
 *     @OA\Property(property="last_name", type="string", example="Pérez"),
 *     @OA\Property(property="email", type="string", format="email", example="juan.perez@example.com"),
 *     @OA\Property(property="password", type="string", example="hashed_password"),
 *     @OA\Property(property="phone_number", type="string", example="+5215512345678"),
 *     @OA\Property(property="status", ref="#/components/schemas/UserStatus", nullable=true, example="activo"),
 *     @OA\Property(property="registration_date", type="string", format="date-time", nullable=true, example="2024-01-15T12:34:56Z"),
 *     @OA\Property(property="emailVerified", type="boolean", description="Indica si el correo del usuario ya ha sido verificado"),
 *     @OA\Property(property="roles", type="array", nullable=true, @OA\Items(ref="#/components/schemas/Role")),
 *     @OA\Property(property="id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="birthdate", type="string", format="date", nullable=true, example="1995-06-15"),
 *     @OA\Property(property="gender", ref="#/components/schemas/UserGender", nullable=true, example="male"),
 *     @OA\Property(property="address", type="array", nullable=true, @OA\Items(type="string"), example={"Calle Falsa 123", "Colonia Centro"}),
 *     @OA\Property(property="stripe_customer_id", type="string", nullable=true, example="cus_ABC123XYZ"),
 *     @OA\Property(property="blood_type", ref="#/components/schemas/UserBloodType", nullable=true, example="O+"),
 *     @OA\Property(property="studentDetail", ref="#/components/schemas/DomainStudentDetail", nullable=true),
 *     @OA\Property(property="created_at", nullable=true ,type="string", format="date", example="2025-09-01"),
 * )
 */
class User
{
    public function __construct(
        public string $curp,
        public string $name,
        public string $last_name,
        public string $email,
        public string $password,
        public string $phone_number,
        /** @var UserStatus */
        public UserStatus $status= UserStatus::ACTIVO,
        public Carbon $registration_date = new Carbon(),
        public bool $emailVerified = false,
        /** @var Role[] */
        public array $roles=[],
        public ?int $id=null,
        public ?Carbon $birthdate = null,
        /** @var UserGender */
        public ?UserGender $gender = null,
        public ?array $address = null,
        /** @var UserBloodType */
        public ?UserBloodType $blood_type = null,
        public ?string $stripe_customer_id = null,
        /** @var StudentDetail */
        public ?StudentDetail $studentDetail=null,
        public ?Carbon $created_at = null,

    )
    {

    }

    public function toResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'status' => $this->status,
            'registration_date' => $this->registration_date->toDateString(),
            'birthdate' => $this->birthdate?->toDateString(),
            'gender' => $this->gender,
            'address' => $this->address,
            'blood_type' => $this->blood_type,
            'stripe_customer_id' => $this->stripe_customer_id,
            'student_detail' => [
                'control_number' => $this->studentDetail->n_control,
                'semester'       => $this->studentDetail->semestre,
                'career'         => $this->studentDetail->career_id,
            ]
        ];
    }

    public function fullName(): string
    {
        return "{$this->name} {$this->last_name}";
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVO;
    }
    public function isDeleted():bool
    {
        return $this->status=== UserStatus::ELIMINADO;
    }

    public function isDisable():bool
    {
        return $this->status=== UserStatus::BAJA;
    }
    public function setStudentDetail(StudentDetail $detail): void
    {
        $this->studentDetail = $detail;
    }
    public function getStudentDetail(): ?StudentDetail {return $this->studentDetail;}
    public function addRole(Role $role): void
    {
        foreach ($this->roles as $r) {
            if ($r->id === $role->id) return;
        }
        $this->roles[] = $role;
    }
    public function getRoles(): array
    {return $this->roles;}

    public function getRole(string $roleName): ?Role
    {
        foreach ($this->roles as $role) {
            if ($role->name === $roleName) return $role;
        }
        return null;
    }


    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name === $roleName) {
                return true;
            }
        }
        return false;
    }

    public function hasAnyRole(array $roleNames): bool
    {
        foreach ($this->roles as $role) {
            if (in_array($role->name, $roleNames, true)) {
                return true;
            }
        }
        return false;
    }

    public function getRoleNames(): array
    {
        return array_map(fn(Role $r) => $r->name, $this->roles);
    }

    public function hasNoRole(): bool
    {
        return empty($this->roles);
    }
    public function isApplicant(): bool
    {
        return $this->hasRole(UserRoles::APPLICANT->value);
    }

    public function isStudent(): bool
    {
        return $this->hasRole(UserRoles::STUDENT->value);
    }

    public function isNewStudent(): bool
    {
        return $this->isStudent() && $this->studentDetail === null;
    }

    public function isParent(): bool
    {
        return $this->hasRole(UserRoles::PARENT->value);
    }

}
