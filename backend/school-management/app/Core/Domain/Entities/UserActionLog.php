<?php

namespace App\Core\Domain\Entities;

class UserActionLog
{
    public function __construct(
        public string $method,
        public string $url,
        public ?array $roles = [],
        public ?int $id = null,
        public ?int $userId = null,
        public ?string $ip = null,

    )
    {
    }

    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'url' => $this->url,
            'roles' => $this->roles ?? [],
            'id' => $this->id ?? null,
            'userId' => $this->userId ?? null,
            'ip' => $this->ip ?? null,
        ];
    }
}
