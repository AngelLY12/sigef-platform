<?php

namespace App\Core\Infraestructure\Repositories\Command\Misc;

use App\Core\Domain\Repositories\Command\Misc\SemesterPromotionsRepInterface;
use Illuminate\Support\Facades\DB;

class EloquentSemesterPromotionsRepository implements SemesterPromotionsRepInterface
{
    public function wasExecutedThisMonth(): bool
    {
        return DB::table('semester_promotions')
            ->whereYear('executed_at', now()->year)
            ->whereMonth('executed_at', now()->month)
            ->exists();
    }

    public function registerExecution(): void
    {
        DB::table('semester_promotions')->insert([
            'executed_at' => now()
        ]);
    }
}
