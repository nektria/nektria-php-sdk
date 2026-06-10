<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Service\ContextService;

use function array_slice;

/**
 * @phpstan-type CachedLog array{
 *     payload: mixed[],
 *     message: string,
 *     project: string,
 *     tenantId: string | null,
 *     tenant: string | null,
 *     authId: string | null,
 *     labels: string[] | null,
 * }
 *
 * @extends SharedRedisCache<CachedLog[]>
 */
class SharedLogCache extends SharedRedisCache
{
    public function __construct(
        private readonly ContextService $contextService,
        string $redisDsn,
        string $env
    ) {
        parent::__construct($redisDsn, $env);
    }

    /**
     * @param CachedLog $log
     */
    public function addLog(array $log): void
    {
        $logs = $this->getItem($this->contextService->traceId()) ?? [];
        $logs[] = $log;
        $logs = array_slice($logs, -20);

        $this->setItem($this->contextService->traceId(), $logs, 300);
    }

    /**
     * @return CachedLog[]
     */
    public function getLogs(): array
    {
        $logs = $this->getItem($this->contextService->traceId()) ?? [];
        $this->removeItem($this->contextService->traceId());

        return $logs;
    }
}
