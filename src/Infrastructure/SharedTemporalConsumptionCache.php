<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Service\ContextService;

/**
 * @extends SharedRedisCache<array<string, array<string, int>>>
 */
class SharedTemporalConsumptionCache extends SharedRedisCache
{
    public function __construct(
        private readonly ContextService $contextService,
        string $redisDsn,
        string $env
    ) {
        parent::__construct($redisDsn, $env);
    }

    public function increase(string $tenantId, string $path): void
    {
        $data = $this->read($tenantId);
        $data[$this->contextService->project()][$path] ??= 0;
        ++$data[$this->contextService->project()][$path];
        $this->setItem($tenantId, $data);
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function read(string $tenantId): array
    {
        return $this->getItem($tenantId) ?? [];
    }

    public function remove(string $tenantId): void
    {
        $this->removeItem($tenantId);
    }
}
