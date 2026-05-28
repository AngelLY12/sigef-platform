<?php

namespace App\Core\Domain\Repositories\Command\Misc;

interface SemesterPromotionsRepInterface
{
    public function wasExecutedThisMonth(): bool;
    public function registerExecution(): void;
}
