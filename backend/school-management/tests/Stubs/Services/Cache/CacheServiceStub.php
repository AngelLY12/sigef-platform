<?php

namespace Tests\Stubs\Services\Cache;

use App\Core\Domain\Repositories\Cache\CacheServiceInterface;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StaffCacheSufix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Domain\Enum\Cache\ParentCacheSufix;
use Closure;

class CacheServiceStub implements CacheServiceInterface
{
    private array $cache = [];
    private array $callLog = [];
    private array $config = [
        'cache-prefixes' => [
            'staff' => 'staff',
            'student' => 'student',
            'parent' => 'parent',
        ]
    ];

    public function get(string $key, $default = null)
    {
        $this->logCall('get', [$key, $default]);
        return $this->cache[$key] ?? $default;
    }

    public function put(string $key, $value, $ttl = null): void
    {
        $this->logCall('put', [$key, $value, $ttl]);
        $this->cache[$key] = $value;

        // NOTA: En tu implementación real, $ttl = null usa forever()
        // En el stub no implementamos TTL por simplicidad
    }

    public function putMany(array $values, $ttl = null): void
    {
        $this->logCall('putMany', [$values, $ttl]);
        foreach ($values as $key => $value) {
            $this->put($key, $value, $ttl);
        }
    }

    public function forget(string $key): void
    {
        $this->logCall('forget', [$key]);
        unset($this->cache[$key]);
    }

    public function has(string $key): bool
    {
        $this->logCall('has', [$key]);
        return array_key_exists($key, $this->cache);
    }

    public function remember(string $key, $ttl, Closure $callback)
    {
        $this->logCall('remember', [$key, $ttl]);

        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->put($key, $value, $ttl);
        return $value;
    }

    public function rememberForever(string $key, Closure $callback)
    {
        $this->logCall('rememberForever', [$key]);
        return $this->remember($key, null, $callback);
    }

    public function increment(string $key, $value = 1)
    {
        $this->logCall('increment', [$key, $value]);
        $current = (int) ($this->cache[$key] ?? 0);
        $newValue = $current + $value;
        $this->cache[$key] = $newValue;
        return $newValue;
    }

    public function decrement(string $key, $value = 1)
    {
        $this->logCall('decrement', [$key, $value]);
        return $this->increment($key, -$value);
    }

    public function makeKey(string $prefixKey, string $suffix): string
    {
        $this->logCall('makeKey', [$prefixKey, $suffix]);
        $prefix = $this->config['cache-prefixes'][$prefixKey] ?? $prefixKey;
        return "$prefix:$suffix";
    }

    public function clearPrefix(string $prefix): void
    {
        $this->logCall('clearPrefix', [$prefix]);
        foreach (array_keys($this->cache) as $key) {
            if (str_starts_with($key, $prefix)) {
                $this->forget($key);
            }
        }
    }

    public function clearKey(string $prefixKey, string $suffix): void
    {
        $this->logCall('clearKey', [$prefixKey, $suffix]);
        $key = $this->makeKey($prefixKey, $suffix);
        $this->clearPrefix($key);
    }

    public function clearStaffCache(): void
    {
        $this->logCall('clearStaffCache', []);

        $suffixes = [
            StaffCacheSufix::DASHBOARD->value . ":*",
            StaffCacheSufix::DEBTS->value . ":*",
            StaffCacheSufix::PAYMENTS->value . ":*",
            StaffCacheSufix::STUDENTS->value . ":*",
        ];

        foreach ($suffixes as $suffix) {
            $this->clearKey(CachePrefix::STAFF->value, $suffix);
        }
    }

    public function clearStudentCache(int $userId): void
    {
        $this->logCall('clearStudentCache', [$userId]);

        $suffixes = [
            StudentCacheSufix::DASHBOARD_USER->value . ":*:$userId",
            StudentCacheSufix::PENDING->value . ":*:$userId",
            StudentCacheSufix::HISTORY->value . ":$userId"
        ];

        foreach ($suffixes as $suffix) {
            $this->clearKey(CachePrefix::STAFF->value, $suffix);
        }
    }

    public function clearParentCache(int $parentId): void
    {
        $this->logCall('clearParentCache', [$parentId]);

        $suffixes = [
            ParentCacheSufix::CHILDREN->value . ":$parentId",
        ];

        foreach ($suffixes as $suffix) {
            $this->clearKey(CachePrefix::PARENT->value, $suffix);
        }
    }

    public function clearCacheWhileConceptChangeStatus(int $userId, PaymentConceptStatus $conceptStatus): void
    {
        $this->logCall('clearCacheWhileConceptChangeStatus', [$userId, $conceptStatus]);

        // Parte STUDENT
        $studentSuffixes = match($conceptStatus) {
            PaymentConceptStatus::ACTIVO => [
                StudentCacheSufix::PENDING->value . ":*:$userId",
                StudentCacheSufix::DASHBOARD_USER->value . ":pending:$userId",
            ],
            PaymentConceptStatus::FINALIZADO => [
                StudentCacheSufix::PENDING->value . ":*:$userId",
                StudentCacheSufix::DASHBOARD_USER->value . ":overdue:$userId",
            ],
            PaymentConceptStatus::ELIMINADO,
            PaymentConceptStatus::DESACTIVADO => [
                StudentCacheSufix::PENDING->value . ":*:$userId",
                StudentCacheSufix::DASHBOARD_USER->value . ":*:$userId",
            ],
        };

        foreach ($studentSuffixes as $suffix) {
            $this->clearKey(CachePrefix::STUDENT->value, $suffix);
        }

        // Parte STAFF
        $staffSuffixes = [
            StaffCacheSufix::DASHBOARD->value . ":pending",
            StaffCacheSufix::DASHBOARD->value . ":concepts",
            StaffCacheSufix::DEBTS->value . ":pending",
            StaffCacheSufix::STUDENTS->value . ":*",
        ];

        foreach ($staffSuffixes as $suffix) {
            $this->clearKey(CachePrefix::STAFF->value, $suffix);
        }
    }

    // Métodos de ayuda para testing - MEJORADOS
    public function getCallLog(): array
    {
        return $this->callLog;
    }

    public function getMethodCalls(string $methodName): array
    {
        return array_filter($this->callLog, fn($call) => $call['method'] === $methodName);
    }

    public function getCallCount(string $methodName): int
    {
        return count($this->getMethodCalls($methodName));
    }

    public function getLastCall(string $methodName): ?array
    {
        $calls = $this->getMethodCalls($methodName);
        return !empty($calls) ? end($calls) : null;
    }

    public function clearCallLog(): void
    {
        $this->callLog = [];
    }

    public function clearAllCache(): void
    {
        $this->cache = [];
    }

    public function setCache(array $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    public function getCache(): array
    {
        return $this->cache;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    private function logCall(string $method, array $args): void
    {
        $this->callLog[] = [
            'method' => $method,
            'args' => $args,
            'timestamp' => microtime(true)
        ];
    }

}
