<?php

namespace Database\Seeders;

use App\Models\Career;
use Illuminate\Database\Seeder;

class CareersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $careers=
        [
            'Técnico Agropecuario',
            'Técnico en Informática',
            'Técnico en Administración para el Emprendimiento'
        ];

        foreach($careers as $career)
        {
            Career::firstOrCreate(['career_name'=>$career]);
        }
    }
}
