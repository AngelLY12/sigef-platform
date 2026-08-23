<?php

namespace App\Core\Domain\ValueObjects\EmailEvent;

interface EmailEventMetadata
{
    public function toArray(): array;

}
