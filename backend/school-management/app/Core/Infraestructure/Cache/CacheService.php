<?php

namespace App\Core\Infraestructure\Cache;

use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StaffCacheSufix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Closure;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    public function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    public function lock(string $lockKey, $ttl = null)
    {
        return Cache::lock($lockKey, $ttl);
    }

    public function getMany(array $keys, $default = null)
    {
        return Cache::getMultiple($keys, $default);
    }

    public function put(string $key, $value, $ttl = null): void
    {
        if ($ttl === null) {
            Cache::forever($key, $value);
        } else {
            Cache::put($key, $value, $ttl);
        }
    }

    public function putMany(array $values, $ttl = null): void
    {
        Cache::putMany($values, $ttl);
    }

    public function add(string $key, $value, $ttl = null): bool
    {
        return Cache::add($key, serialize($value), $ttl);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    public function remember(array $tags,string $key, int $ttl, Closure $callback)
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    public function rememberForever(string $key, Closure $callback)
    {
        return Cache::rememberForever($key, $callback);
    }

    public function flushTags(array $tags)
    {
        return Cache::tags($tags)->flush();
    }

    public function increment(string $key, $value = 1)
    {
        return Cache::increment($key, $value);
    }

    public function decrement(string $key, $value = 1)
    {
        return Cache::decrement($key, $value);
    }

    public function makeKey(string $prefixKey, string $suffix): string
    {
        $prefix = config("cache-prefixes.$prefixKey");
        return "{$prefix}{$suffix}";
    }

    public function clearStaffCache(): void
    {
        $this->flushTags([CachePrefix::STAFF->value, StaffCacheSufix::DASHBOARD->value]);
        $this->flushTags([CachePrefix::STAFF->value, StaffCacheSufix::DEBTS->value]);
        $this->flushTags([CachePrefix::STAFF->value, StaffCacheSufix::PAYMENTS->value]);

    }

    public function clearStudentCache(int $userId):void
    {
        $this->flushTags([CachePrefix::STUDENT->value, StudentCacheSufix::DASHBOARD_USER->value, "userId:{$userId}"]);
        $this->flushTags([CachePrefix::STUDENT->value, StudentCacheSufix::PENDING->value, "userId:{$userId}"]);
        $this->flushTags([CachePrefix::STUDENT->value, StudentCacheSufix::HISTORY->value, "userId:{$userId}"]);
    }

    public function clearCacheWhileConceptChangeStatus(int $userId, PaymentConceptStatus $conceptStatus): void
    {
        $studentTags = [CachePrefix::STUDENT->value, "userId:{$userId}"];
        $additionalTags = match ($conceptStatus) {
            PaymentConceptStatus::ACTIVO => [StudentCacheSufix::PENDING->value],
            PaymentConceptStatus::FINALIZADO=> [StudentCacheSufix::PENDING->value, "overdue"],
            PaymentConceptStatus::ELIMINADO,
            PaymentConceptStatus::DESACTIVADO=>[StudentCacheSufix::PENDING->value, StudentCacheSufix::DASHBOARD_USER->value, "overdue"],
        };

        $this->flushTags(array_merge($studentTags, $additionalTags));
        $this->clearStaffCache();
    }
}
