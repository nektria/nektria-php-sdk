<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Infrastructure\SharedTenantCache;

/**
 * @phpstan-import-type TenantMetadataArray from SharedTenantCache
 */
readonly class TenantMetadata extends Metadata
{
    public const string ECHO_MODE_ALWAYS = 'always';

    public const string ECHO_MODE_DEFAULT = 'default';

    public const string GRID_MODE_COMMON = 'common';

    public const string GRID_MODE_DEFAULT = 'default';

    public function autoDuplicateLastWeek(): bool
    {
        return $this->getField('autoDuplicateLastWeek') ?? true;
    }

    /** @return string[] */
    public function availableTags(): array
    {
        return $this->getField('availableTags') ?? [];
    }

    public function blockWarehouse(): bool
    {
        return $this->getField('blockWarehouse') ?? true;
    }

    /**
     * @return string[]
     */
    public function capacitiesEmailRecipients(): array
    {
        return $this->getField('capacitiesEmailRecipients') ?? [];
    }

    public function countComplementaryOrders(): bool
    {
        return $this->getField('countComplementaryOrders') ?? true;
    }

    public function dayOffExtendsCutoff(): bool
    {
        return $this->getField('dayOffExtendsCutoff') ?? false;
    }

    public function deliveryTime(): ?int
    {
        return $this->getField('deliveryTime');
    }

    public function ecoMode(): string
    {
        return $this->getField('ecoMode') ?? self::ECHO_MODE_DEFAULT;
    }

    public function enableVirtualTimeWindows(): bool
    {
        return $this->getField('enableVirtualTimeWindows') ?? false;
    }

    public function expressGridDisabled(): bool
    {
        return $this->getField('expressGridDisabled') ?? false;
    }

    public function expressGridEnabled(): bool
    {
        return !$this->expressGridDisabled();
    }

    public function extendPickingShiftsDisabled(): bool
    {
        return $this->getField('extendPickingShiftsDisabled') ?? true;
    }

    public function extraLongSpeed(): int
    {
        return $this->getField('extraLongSpeed') ?? 1440;
    }

    public function forceDriverAssignation(): bool
    {
        return $this->getField('forceDriverAssignation') ?? false;
    }

    public function forceTags(): bool
    {
        return $this->getField('forceTags') ?? false;
    }

    public function freeSlotPriceForincentivizedShoppers(): bool
    {
        return $this->getField('freeSlotPriceForincentivizedShoppers') ?? false;
    }

    public function getDiscordChannelFor(string $channel): ?string
    {
        return $this->getField("{$channel}Channel");
    }

    public function gridMode(): string
    {
        return $this->getField('gridMode') ?? self::GRID_MODE_DEFAULT;
    }

    public function gridVesion(): int
    {
        return $this->getField('gridVersion') ?? 1;
    }

    public function gridViewerOrdersPrefix(): string
    {
        return $this->getField('gridViewerOrdersPrefix') ?? '';
    }

    public function hideCapacityAlarms(): bool
    {
        return $this->getField('hideCapacityAlarms') ?? false;
    }

    public function hideFutureSlotsStatusForGridViewers(): bool
    {
        return $this->getField('hideFutureSlotsStatusForGridViewers') ?? false;
    }

    /**
     * @return string[]
     */
    public function ignoreRoutesOnLogsList(): array
    {
        return $this->getField('ignoreRoutesOnLogsList') ?? [];
    }

    public function longSpeed(): int
    {
        return $this->getField('longSpeed') ?? 720;
    }

    public function nextStepEnabled(): bool
    {
        return $this->getField('nextStepEnabled') ?? false;
    }

    public function parkingTime(): int
    {
        return $this->getField('parkingTime') ?? 3;
    }

    public function proxyHost(): ?string
    {
        return $this->getField('proxyHost');
    }

    public function recoverCoords(): bool
    {
        return $this->getField('recoverCoords') ?? false;
    }

    public function shortSpeed(): int
    {
        return $this->getField('shortSpeed') ?? 240;
    }

    /**
     * @return string[]
     */
    public function specialClients(): array
    {
        return $this->getField('specialClients') ?? [];
    }

    public function syncRMOrder(): bool
    {
        return $this->getField('syncRMOrder') ?? false;
    }

    public function syncRMShift(): bool
    {
        return $this->getField('syncRMShift') ?? false;
    }

    public function syncRMWarehouse(): bool
    {
        return $this->getField('syncRMWarehouse') ?? false;
    }

    public function useAddressInsteadOfShopperCode(): bool
    {
        return $this->getField('useAddressInsteadOfShopperCode') ?? false;
    }
}
