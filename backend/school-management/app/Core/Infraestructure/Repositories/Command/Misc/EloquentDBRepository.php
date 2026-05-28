<?php

namespace App\Core\Infraestructure\Repositories\Command\Misc;

use App\Core\Domain\Repositories\Command\Misc\DBRepInterface;
use Illuminate\Support\Facades\DB;

class EloquentDBRepository implements DBRepInterface
{
    public function checkDBStatus(): bool
    {
        DB::connection()->getPdo();
        $tables = DB::select('SHOW TABLES');
        return count($tables) > 0;
    }

    public function getFragmentation(): array
    {
        return DB::select("
            SELECT
                table_name,
                data_length,
                data_free,
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            AND data_free > 0
            ORDER BY data_free DESC
        ");
    }

    public function optimizeTables(array $tables): void
    {
        if(empty($tables)){
            return;
        }

        $tableList = implode("','", $tables);
        DB::statement("OPTIMIZE TABLE {$tableList}");
    }
}
