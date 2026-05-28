<?php

namespace App\Core\Application\Traits;

use App\Core\Domain\Enum\Cache\AdminCacheSufix;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\Conflict\IdempotencyExistsException;
use App\Exceptions\Conflict\IdempotencyTimeoutException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait HasCache
{
    private CacheService $cacheService;
    const CACHE_SHORT = 30 * 60;    // 30 minutos
    const CACHE_MEDIUM = 120 * 60;  // 2 horas
    const CACHE_LONG = 1440 * 60;   // 24 horas
    const CACHE_WEEK = 10080 * 60;

    public function setCacheService(CacheService $cacheService): void
    {
        $this->cacheService = $cacheService;
    }

    private function cache(
        array $tags,
        string $key,
        callable $callback,
        int $ttl = self::CACHE_MEDIUM,
        bool $forceRefresh = false,
    )
    {

        if ($forceRefresh) {
            $this->cacheService->flushTags($tags);
        }

        return $this->cacheService->remember($tags,$key, $ttl, $callback);
    }

    protected function shortCache(
        string $key,
        callable $callback,
        array $tags = [],
        bool $forceRefresh = false
    ) {
        return $this->cache($tags, $key, $callback, self::CACHE_SHORT, $forceRefresh);
    }

    protected function mediumCache(
        string $key,
        callable $callback,
        array $tags = [],
        bool $forceRefresh = false
    ) {
        return $this->cache($tags, $key, $callback, self::CACHE_MEDIUM, $forceRefresh);
    }

    protected function longCache(
        string $key,
        callable $callback,
        array $tags = [],
        bool $forceRefresh = false
    ) {
        return $this->cache($tags, $key, $callback, self::CACHE_LONG, $forceRefresh);
    }

    protected function weeklyCache(
        string $key,
        callable $callback,
        array $tags = [],
        bool $forceRefresh = false
    ) {
        return $this->cache($tags, $key, $callback, self::CACHE_WEEK, $forceRefresh);
    }

    protected function generateCacheKey(string $prefix, string $suffix, array $params = []): string
    {
        $hashPart = '';
        if (!empty($params)) {
            ksort($params);
            $hashPart = ':' . substr(md5(serialize($params)), 0, 8);
        }
        $fullSuffix = $suffix . $hashPart;

        return $this->cacheService->makeKey($prefix, $fullSuffix);
    }

    protected function idempotent(string $operation, array $attributes, callable $callback, ?int $ttlSeconds = 120)
    {
        $attributes['_actor'] = auth()->id();

        $normalized = $this->normalizeAttributes($attributes);

        $hash = hash(
            'sha256',
            json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)
        );

        $cacheKey = "idempotent:{$operation}:{$hash}";
        $lockKey  = "lock:{$cacheKey}";

        $lockSeconds = 40;

        $lock = $this->cacheService->lock($lockKey, $lockSeconds);

        try {
            return $lock->block($lockSeconds, function () use ($cacheKey, $ttlSeconds, $callback) {

                if (!is_null($result = $this->cacheService->get($cacheKey))) {
                    return $result;
                }

                $result = $callback();

                $this->cacheService->put(
                    $cacheKey,
                    $result,
                    $ttlSeconds
                );

                return $result;
            });

        }catch (LockTimeoutException $e)
        {
            if (!is_null($result = $this->cacheService->get($cacheKey))) {
                return $result;
            }

            throw new IdempotencyTimeoutException($operation);
        }

    }


    private function normalizeAttributes(array $attributes): array
    {
        foreach ($attributes as $key => $value) {

            if (is_array($value)) {

                $attributes[$key] = $this->normalizeAttributes($value);

                if ($this->isAssoc($attributes[$key])) {
                    ksort($attributes[$key]);
                } else {
                    sort($attributes[$key]);
                }
            }
        }

        ksort($attributes);

        return $attributes;
    }

    private function isAssoc(array $array): bool
    {
        if ($array === []) return false;

        return array_keys($array) !== range(0, count($array) - 1);
    }


}
