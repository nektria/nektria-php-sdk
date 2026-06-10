<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Dto\LocalClock;

/**
 * @phpstan-type TemporalAreaDailyInfo array{
 *     area: string,
 *     cheatedDeliveries: int,
 *     date: LocalClock,
 *     earlyDeliveries: int,
 *     gridsRequested: int,
 *     invalidAvailabilityRequested: int,
 *     lateDeliveries: int,
 *     onTimeDeliveries: int,
 *     ordersCreated: int,
 *     ordersDelivered: int,
 *     warehouseId: string|null,
 *  }
 *
 * @extends SharedRedisCache<array<string, array<string, TemporalAreaDailyInfo>>>
 */
class SharedTemporalAreaDailyInfoCache extends SharedRedisCache
{
    /**
     * @return TemporalAreaDailyInfo
     */
    public function read(string $tenantId, LocalClock $date, string $area): array
    {
        $tenantData = $this->getItem($tenantId) ?? [];
        $tenantData = $tenantData[$date->dateString()] ?? [];
        $finalData = $tenantData[$area] ?? [];

        return [
            'area' => $area,
            'cheatedDeliveries' => $finalData['cheatedDeliveries'] ?? 0,
            'date' => $date,
            'earlyDeliveries' => $finalData['earlyDeliveries'] ?? 0,
            'gridsRequested' => $finalData['gridsRequested'] ?? 0,
            'invalidAvailabilityRequested' => $finalData['invalidAvailabilityRequested'] ?? 0,
            'lateDeliveries' => $finalData['lateDeliveries'] ?? 0,
            'onTimeDeliveries' => $finalData['onTimeDeliveries'] ?? 0,
            'ordersCreated' => $finalData['ordersCreated'] ?? 0,
            'ordersDelivered' => $finalData['ordersDelivered'] ?? 0,
            'warehouseId' => $finalData['warehouseId'] ?? null,
        ];
    }

    /**
     * @return array<string, array<string, TemporalAreaDailyInfo>>
     */
    public function readFull(string $tenantId): array
    {
        return $this->getItem($tenantId) ?? [];
    }

    public function remove(string $tenantId): void
    {
        $this->removeItem($tenantId);
    }

    /**
     * @param TemporalAreaDailyInfo $data
     */
    public function save(string $tenantId, LocalClock $date, string $area, array $data): void
    {
        $tenantData = $this->getItem($tenantId) ?? [];
        $tenantData[$date->dateString()][$area] = $data;
        $this->setItem($tenantId, $tenantData);
    }
}
