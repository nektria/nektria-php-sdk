<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\Clock;
use Nektria\Dto\LocalClock;
use Nektria\Exception\RequestException;
use Nektria\Infrastructure\SharedUserV2Cache;

/**
 * @phpstan-type MetricsDeliveryInfo array{
 *      orderNumber: string,
 *      at: Clock
 * }
 *
 * @phpstan-type MetricsPing array{
 *       response: string
 *  }
 */
readonly class MetricsClient extends AbstractService
{
    public function __construct(
        protected SharedUserV2Cache $sharedUserCache,
        private string $metricsHost
    ) {
        parent::__construct();
    }

    public function checkFraudulentOrderFromHash(string $hash): bool
    {
        if ($this->contextService()->isTest()) {
            return false;
        }

        try {
            $this->requestClient()->get(
                "{$this->metricsHost}/api/admin/dangerous-addresses/slug/{$hash}",
                headers: $this->getHeaders(),
            );

            return true;
        } catch (RequestException $e) {
            if ($e->response()->status === 404) {
                return false;
            }

            throw $e;
        }
    }

    public function deliverOrder(string $orderNumber, LocalClock $at): void
    {
        $this->requestClient()->patch(
            "{$this->metricsHost}/api/admin/orders/{$orderNumber}/deliver",
            data: [
                'at' => $at->dateTimeString(),
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return MetricsPing
     */
    public function ping(): array
    {
        return $this->requestClient()->get("{$this->metricsHost}/ping")->json();
    }

    /**
     * @param int[]|null $geoPolygonsContourSizes
     */
    public function saveWarehouse(
        string $warehouseId,
        ?string $name = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?array $geoPolygonsContourSizes = null,
        ?string $travelMode = null,
    ): void {
        if ($this->contextService()->isTest()) {
            return;
        }

        $this->requestClient()->put(
            "{$this->metricsHost}/api/admin/warehouses/{$warehouseId}",
            data: [
                'name' => $name,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'travelMode' => $travelMode,
                'geopolygonsContourSizes' => $geoPolygonsContourSizes,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveWarehouseDailyInfo(
        string $warehouseId,
        LocalClock $date,
        ?float $globalConnectivity = null,
        ?float $routeConnectivity = null,
        ?float $slotConnectivity = null
    ): void {
        if ($this->contextService()->isTest()) {
            return;
        }

        $this->requestClient()->put(
            "{$this->metricsHost}/api/admin/warehouses/{$warehouseId}/daily-infos",
            data: [
                'date' => $date->dateString(),
                'globalConnectivity' => $globalConnectivity,
                'routeConnectivity' => $routeConnectivity,
                'slotConnectivity' => $slotConnectivity,
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function getHeaders(): array
    {
        $tenantId = $this->contextService()->getExtra('tenantId') ?? 'none';
        $apiKey =
            $this->sharedUserCache->read("SYSTEM_{$tenantId}")->apiKey ??
            $this->sharedUserCache->read("ADMIN_{$tenantId}")->apiKey ??
            'none';

        return [
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
            'X-Api-Id' => $apiKey,
            'X-Nektria-App' => 'metrics',
            'X-Trace' => $this->contextService()->traceId(),
            'X-Origin' => $this->contextService()->project(),
        ];
    }
}
