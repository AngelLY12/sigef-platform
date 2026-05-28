<?php

namespace Tests\Stubs\Repositories\Command;
use App\Core\Domain\Repositories\Command\Misc\DBRepInterface;

class DBRepStub implements DBRepInterface
{
    private bool $dbStatus = true;
    private bool $throwConnectionError = false;
    private int $tablesCount = 5;
    private array $fragmentationData = [];


    public function checkDBStatus(): bool
    {
        if ($this->throwConnectionError) {
            throw new \RuntimeException('Database connection error');
        }

        return $this->dbStatus && $this->tablesCount > 0;
    }

    public function getFragmentation(): array
    {
        // Datos de ejemplo para testing
        return [
            (object) [
                'table_name' => 'users',
                'data_length' => 1048576,
                'data_free' => 65536,
            ],
            (object) [
                'table_name' => 'posts',
                'data_length' => 524288,
                'data_free' => 32768,
            ],
            (object) [
                'table_name' => 'comments',
                'data_length' => 262144,
                'data_free' => 16384,
            ],
        ];
    }

    public function optimizeTables(array $tables): void
    {
        if (empty($tables)) {
            return;
        }

    }


    public function setDBStatus(bool $status): self
    {
        $this->dbStatus = $status;
        return $this;
    }

    public function shouldThrowConnectionError(bool $throw = true): self
    {
        $this->throwConnectionError = $throw;
        return $this;
    }

    public function setTablesCount(int $count): self
    {
        $this->tablesCount = $count;
        return $this;
    }

    public function setFragmentationData(array $data): void
    {
        $this->fragmentationData = $data;
    }
}
