<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Application\DTO\Response\General\OptimizeDatabaseResult;
use App\Core\Domain\Repositories\Command\Misc\DBRepInterface;

class OptimizeDatabaseUseCase
{
    public function __construct(
        private DBRepInterface $dbRep
    ){}

    public function execute(int $minFreeBytes = 100_000_000): OptimizeDatabaseResult
    {
        $tables = [];
        $totalFree = 0;
        $fragmentation = collect($this->dbRep->getFragmentation())
            ->filter(function ($t) use ($minFreeBytes, &$totalFree) {
                if ($t->data_free >= $minFreeBytes) {
                    $totalFree += (int) $t->data_free;
                    return true;
                }
                return false;
            });


        $tables = $fragmentation->pluck('table_name')->toArray();

        if (!empty($tables)) {
            $this->dbRep->optimizeTables($tables);
        }

        return OptimizeDatabaseResult::create(
            optimized: !empty($tables),
            tables: $tables,
            totalFragmentationBytes: $totalFree
        );
    }


}
