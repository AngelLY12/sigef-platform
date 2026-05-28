<?php

namespace App\Core\Domain\Repositories\Command\Misc;

interface DBRepInterface
{
    public function checkDBStatus(): bool;
    public function getFragmentation(): array;
    public function optimizeTables(array $tables): void;


}
