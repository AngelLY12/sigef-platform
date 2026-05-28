<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(PermissionsSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(CareersSeeder::class);
        $this->call(AdminUserSeeder::class);

    }

}
