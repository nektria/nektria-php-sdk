<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Dto\Clock;
use RuntimeException;
use Throwable;

use function is_bool;

/**
 * @template T
 */
abstract class InternalRedisCache extends RedisCache
{
    public function __construct(string $redisDsn, string $redisPrefix, string $env)
    {
        parent::__construct($redisDsn, $env, $redisPrefix);

        $data = parse_url($redisDsn);
        if ($data === false) {
            throw new RuntimeException("Invalid redis dsn: {$redisDsn}");
        }
    }

    public function beginTransaction(): void
    {
        $this->init()->multi();
    }

    public function closeTransaction(): void
    {
        $this->init()->exec();
    }

    public function empty(): void
    {
        try {
            $this->init()->eval("for _,k in ipairs(redis.call('keys','{$this->fqn}:*')) do redis.call('del',k) end");

            $lastError = $this->init()->getLastError();
            if ($lastError !== null) {
                $this->init()->clearLastError();

                throw new RuntimeException($lastError);
            }
        } catch (Throwable) {
        }
    }

    public function fullRedisEmpty(): void
    {
        try {
            $this->init()->flushDB();

            $lastError = $this->init()->getLastError();
            if ($lastError !== null) {
                $this->init()->clearLastError();

                throw new RuntimeException($lastError);
            }
        } catch (Throwable) {
        }
    }

    /**
     * @return T|null
     */
    public function getItem(string $key): mixed
    {
        try {
            $item = $this->init()->get("{$this->fqn}:{$key}");

            if (is_bool($item)) {
                return null;
            }

            $lastError = $this->init()->getLastError();
            if ($lastError !== null) {
                $this->init()->clearLastError();

                throw new RuntimeException($lastError);
            }

            $ser = unserialize($item, [
                'allowed_classes' => true,
            ]);

            if (is_bool($ser)) {
                return null;
            }

            return $ser;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param string[] $keys
     * @return array<string, T|null>
     * @return ($returnMissing is true ? array<string, T|null> : array<string, T>)
     */
    public function getItems(array $keys, bool $returnMissing = false): array
    {
        try {
            $realKeys = array_map(fn (string $key) => "{$this->fqn}:{$key}", $keys);
            $items = $this->init()->mget($realKeys);

            $results = [];
            foreach ($keys as $index => $key) {
                if ($items[$index] === false) {
                    if ($returnMissing) {
                        $results[$key] = null;
                    }
                } else {
                    $results[$key] = unserialize($items[$index], [
                        'allowed_classes' => true,
                    ]);
                }
            }
        } catch (Throwable) {
            return [];
        }

        return $results;
    }

    public function removeItem(string $key): void
    {
        try {
            $this->init()->del("{$this->fqn}:{$key}");

            $lastError = $this->init()->getLastError();
            if ($lastError !== null) {
                $this->init()->clearLastError();

                throw new RuntimeException($lastError);
            }
        } catch (Throwable) {
        }
    }

    /**
     * @param T $item
     */
    public function setItem(string $key, $item, Clock | int $ttl = 300): void
    {
        if ($ttl instanceof Clock) {
            $ttl = $ttl->diff(Clock::now());
        }

        $ttl = max(1, $ttl);

        try {
            $this->init()->set("{$this->fqn}:{$key}", serialize($item), $ttl);

            $lastError = $this->init()->getLastError();
            if ($lastError !== null) {
                $this->init()->clearLastError();

                throw new RuntimeException($lastError);
            }
        } catch (Throwable) {
        }
    }
}
