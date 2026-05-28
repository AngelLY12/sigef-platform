<?php

namespace Database\Seeders;

use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = config('admin.password');
        $email = config('admin.email');
        $first_name = config('admin.first_name');
        $last_name = config('admin.last_name');
        $phone = config('admin.phone');
        $curp = config('admin.curp');
        if(!$password || !$email || !$first_name || !$last_name || !$phone || !$curp)
        {
            throw new \RuntimeException(
                "No existen datos para crear al administrador, llena las variables de entorno correspondientes"
            );
        }
        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $first_name,
                'last_name' => $last_name,
                'email' =>  $email,
                'password' => Hash::make($password),
                'phone_number' => $phone,
                'birthdate' => '1995-09-24',
                'gender' => UserGender::HOMBRE,
                'curp' => $curp,
                'address' => [
                    'street' => 'Calle Falsa 123',
                    'city' => 'Ciudad de Ejemplo',
                    'state' => 'Estado Ejemplo',
                    'zip' => '12345'
                ],
                'stripe_customer_id' => null,
                'blood_type' => UserBloodType::O_POSITIVE,
                'registration_date' => now()->toDateString(),
                'status' => UserStatus::ACTIVO,
            ]
        );

        $admin->syncRoles([UserRoles::ADMIN->value]);
    }
}
