<?php

namespace App\Core\Domain\Repositories\Cache;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Closure;


interface CacheServiceInterface
{
    public function get(string $key, $default = null);
    public function put(string $key, $value, $ttl = null): void;
    public function putMany(array $values, $ttl = null): void;
    public function forget(string $key): void;
    public function has(string $key): bool;

    // Métodos de recordar/recuperar
    public function remember(string $key, $ttl, Closure $callback);
    public function rememberForever(string $key, Closure $callback);

    // Métodos atómicos
    public function increment(string $key, $value = 1);
    public function decrement(string $key, $value = 1);

    // Métodos de utilidad para claves
    public function makeKey(string $prefixKey, string $suffix): string;

    // Métodos de limpieza
    public function clearPrefix(string $prefix): void;
    public function clearKey(string $prefixKey, string $suffix): void;

    // Métodos específicos de dominio
    public function clearStaffCache(): void;
    public function clearStudentCache(int $userId): void;
    public function clearParentCache(int $parentId): void;
    public function clearCacheWhileConceptChangeStatus(int $userId, PaymentConceptStatus $conceptStatus): void;

}
