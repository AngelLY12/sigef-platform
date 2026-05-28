<?php

namespace App\Core\Infraestructure\Mappers;

use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Models\User as EloquentUser;
use App\Core\Domain\Entities\User as DomainUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class UserMapper{
    public static function toDomain(EloquentUser $user): DomainUser
    {
        $addressData = null;
        if (!empty($user->address)) {
            if (is_string($user->address)) {
                $addressData = json_decode($user->address, true);
            } elseif (is_array($user->address)) {
                $addressData = $user->address;
            }
        }
        $domainUser = new DomainUser(
            curp: $user->curp,
            name: $user->name,
            last_name: $user->last_name,
            email: $user->email,
            password: $user->password,
            phone_number: $user->phone_number,
            status: $user->status,
            registration_date: $user->registration_date,
            emailVerified: $user->hasVerifiedEmail(),
            id: $user->id,
            birthdate: $user->birthdate,
            gender: $user->gender,
            address: $addressData,
            blood_type: $user->blood_type,
            stripe_customer_id: $user->stripe_customer_id,
            created_at: $user->created_at
        );
        if ($user->relationLoaded('studentDetail') && $user->studentDetail !== null) {
            $domainUser->setStudentDetail(StudentDetailMapper::toDomain($user->studentDetail));
        }
        if($user->relationLoaded('roles'))
        {
            foreach ($user->roles as $role){
                $domainUser->addRole(RolesAndPermissionMapper::toRoleDomain($role));
            }
        }

        return $domainUser;

    }
    public static function toPersistence(CreateUserDTO $user): array
    {
        return [
            'name' => $user->name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'password' => Hash::make($user->password),
            'phone_number' =>$user->phone_number,
            'birthdate' => $user->birthdate,
            'gender' => $user->gender,
            'curp' => $user->curp,
            'address' => $user->address,
            'stripe_customer_id' => $user->stripe_customer_id ?? null,
            'blood_type' => $user->blood_type,
            'registration_date' => $user->registration_date ?? Carbon::now() ,
            'status' => $user->status,
        ];
    }
}
