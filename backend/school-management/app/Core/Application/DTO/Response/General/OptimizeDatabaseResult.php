<?php

namespace App\Core\Application\DTO\Response\General;

class OptimizeDatabaseResult
{
    public function __construct(
        public readonly bool $optimized,
        public readonly array $tables,
        public readonly int $totalFragmentationBytes
    ) {}

    public function isEmpty(): bool
    {
        return empty($this->tables);
    }

    public static function create(bool $optimized, array $tables, int $totalFragmentationBytes): self
    {
        return new self(
            optimized:$optimized,
            tables: $tables,
            totalFragmentationBytes: $totalFragmentationBytes
        );
    }

}
