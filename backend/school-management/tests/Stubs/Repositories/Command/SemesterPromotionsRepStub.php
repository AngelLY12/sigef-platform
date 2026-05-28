<?php

namespace Tests\Stubs\Repositories\Command;

use App\Core\Domain\Repositories\Command\Misc\SemesterPromotionsRepInterface;

class SemesterPromotionsRepStub implements SemesterPromotionsRepInterface
{
    private bool $throwDatabaseError = false;
    private bool $executedThisMonth = false;

    public function wasExecutedThisMonth(): bool
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        return $this->executedThisMonth;
    }

    public function registerExecution(): void
    {
        if ($this->throwDatabaseError) {
            throw new \RuntimeException('Database error');
        }

        $this->executedThisMonth = true;
    }

    public function shouldThrowDatabaseError(bool $throw = true): self
    {
        $this->throwDatabaseError = $throw;
        return $this;
    }

    public function setExecutedThisMonth(bool $executed): self
    {
        $this->executedThisMonth = $executed;
        return $this;
    }

    public function clearExecution(): self
    {
        $this->executedThisMonth = false;
        return $this;
    }
}
