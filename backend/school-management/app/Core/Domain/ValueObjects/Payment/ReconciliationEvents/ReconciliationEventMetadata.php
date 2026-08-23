<?php

namespace App\Core\Domain\ValueObjects\Payment\ReconciliationEvents;

interface ReconciliationEventMetadata
{
    public function toArray(): array;

}
