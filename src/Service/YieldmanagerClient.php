<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Dto\Address;
use Nektria\Dto\LocalClock;
use Nektria\Dto\TenantMetadata;
use Nektria\Exception\RequestException;
use Nektria\Infrastructure\SharedUserV2Cache;

/**
 * @phpstan-type YMTimeWindow array{
 *     areas: string[],
 *     children: string[],
 *     createdAt: string,
 *     id: string,
 *     multiplier: int,
 *     name: string,
 *     nextConnectivityTime: int,
 *     orderCount: int,
 *     parent: string|null,
 *     previousConnectivityTime: int,
 *     price: int,
 *     secondaryAreas: string[],
 *     shiftId: string,
 *     status: string,
 *     tags: string[],
 *     timeCapacity: int,
 *     timeOrderRestrictions: array{
 *         maxCapacity: number,
 *         threshold: number,
 *     }[],
 *     timeRange: array{
 *         endTime: string,
 *         startTime: string,
 *     },
 *     updatedAt: string,
 *     usedTimeCapacity: int,
 *     zone: array{
 *         enabled: bool,
 *         createdAt: string,
 *         id: string,
 *         name: string,
 *         points: array<array<float>>,
 *         tenantId: string,
 *         updatedAt: string,
 *         warehouseId: string,
 *     }|null,
 * }
 *
 * @phpstan-type YMWarehouse array{
 *     address: YMAddress,
 *     applyIncentives: bool,
 *     areas: string[],
 *     createdAt: string,
 *     dailyInfo: array{
 *         countdownCutoff: string|null,
 *         countdownMaxOrders: int|null,
 *         countdownMaxProductLines: int|null,
 *         countdownMaxWeight: int|null,
 *         date: string,
 *         dayOff: bool|null,
 *         disableMarketingCampaign: bool|null,
 *         maxOrders: int|null,
 *         maxProductLines: int|null,
 *         maxStorePickUpOrders: int|null,
 *         maxWeight: int|null,
 *     }[],
 *     daysOff: string[],
 *     enabled: bool,
 *     extraWindowsForVip: int,
 *     id: string,
 *     itcEnabled: bool,
 *     maxOrders: int|null,
 *     maxProductLines: int|null,
 *     maxShifts: int|null,
 *     maxStorePickUpOrders: int|null,
 *     maxWeight: int|null,
 *     name: string,
 *     pricePerOrderGoal: float,
 *     timezone: string,
 *     updatedAt: string,
 *     vipSettings: array{
 *         enabled: bool,
 *         extraOrderRestrictions: int,
 *         extraShiftWeight: int,
 *         extraTimeWindowCapacity: int,
 *         extraTimeWindowLinkedCapacity: int,
 *     },
 *     warehouseCode: ?string
 * }
 *
 * @phpstan-type YMWarehouseResume array{
 *     areas: string[],
 *     averageConvertedTransportDelayTime: int,
 *     averageConvertedWarehouseDelayTime: int,
 *     averageNonConvertedTransportDelayTime: int,
 *     averageNonConvertedWarehouseDelayTime: int,
 *     avgNonFirstWindowAddresses: int,
 *     convertedAddresses: int,
 *     cost: int,
 *     costPerOrder: int,
 *     dataUntil: string,
 *     date: string,
 *     firstWindowAddresses: int,
 *     gridsWithExpressAvailable: int,
 *     gridsWithExpressTotal: int,
 *     gridsWithN0Available: int,
 *     gridsWithN1Available: int,
 *     gridsWithN2Available: int,
 *     id: string,
 *     lastOrder: string,
 *     name: string,
 *     nonComplementaryOrders: int,
 *     orders: int,
 *     ordersMinutes: int,
 *     ordersWeight: int,
 *     recentOrders: int,
 *     saturation: int,
 *     shifts: int,
 *     shiftsWeight: int,
 *     timeWindows: int,
 *     timeWindowsMinutes: int,
 *     timezone: string,
 *     totalAddresses: int,
 *     totalGrids: int,
 * }
 *
 * @phpstan-type YMWarehouseRule array{
 *     appliedShifts: string[],
 *     capacityRatio: float,
 *     createdAt: string,
 *     cutoff: string|null,
 *     date: string,
 *     distance: int,
 *     id: string,
 *     maxOrders: int|null,
 *     maxProductLines: int|null,
 *     maxWeight: int|null,
 *     metadata: array<string, mixed>,
 *     multiplier: float,
 *     orders: int,
 *     penalty: int,
 *     rule: string,
 *     shifts: string[],
 *     timeLeft: int,
 *     timeWindowsInvolved: int,
 *     totalStock: int,
 *     updatedAt: string,
 *     usedStock: int,
 *     warehouseId: string,
 * }
 *
 * @phpstan-type YMShift array{
 *     applySecondaryAreas: bool,
 *     areas: string[],
 *     color: string,
 *     createdAt: string,
 *     cutoff: string,
 *     date: string,
 *     id: string,
 *     localCutoff: string,
 *     name: string,
 *     nonComplementaryOrders: int,
 *     orders: int,
 *     penalty: int,
 *     showInMap: bool,
 *     status: string,
 *     tags: string[],
 *     timeWindows: YMTimeWindow[],
 *     updatedAt: string,
 *     usedWeightCapacity: int,
 *     warehouseId: string,
 *     weightCapacity: int,
 * }
 *
 * @phpstan-type YMOrder array{
 *     address: YMAddress,
 *     area: string,
 *     createdAt: string,
 *     daysOpened: int,
 *     deliveryTime: int,
 *     id: string,
 *     incentivized: bool,
 *     locationId: string,
 *     orderNumber: string,
 *     parkingTime: int,
 *     preparationTime: int,
 *     productLines: int,
 *     reason: string,
 *     selectedSlotDelayTime: int,
 *     shiftId: string|null,
 *     shopperCode: string|null,
 *     timeRange: array{
 *         endTime: string,
 *         startTime: string
 *     },
 *     timeWindowId: string|null,
 *     transportDelayTime: int,
 *     travelTime: int,
 *     updatedAt: string,
 *     warehouseDelayTime: int,
 *     warehouseId: string|null,
 *     weight: int,
 *     windowsOpened: int
 * }
 *
 * @phpstan-type YMAddress array{
 *     addressLine1: string,
 *     addressLine2: string,
 *     city: string,
 *     countryCode: string,
 *     createdAt: string,
 *     elevator: ?bool,
 *     latitude: float,
 *     longitude: float,
 *     postalCode: string,
 *     updatedAt: string,
 * }
 *
 * @phpstan-type YMExpressOrder array{
 *     address: YMAddress,
 *     area: string,
 *     createdAt: string,
 *     deliveryTime: int,
 *     expressPickerId: string|null,
 *     expressShiftId: string|null,
 *     id: string,
 *     lastStatusUpdate: string,
 *     locationId: string,
 *     orderNumber: string,
 *     parkingTime: int,
 *     preparationTime: int,
 *     productLines: int,
 *     shopperCode: string|null,
 *     status: string,
 *     travelTime: int,
 *     updatedAt: string,
 *     warehouseId: string|null,
 *     weight: int
 * }
 *
 * @phpstan-type YMPing array{
 *     response: string
 * }
 */
readonly class YieldmanagerClient extends AbstractService
{
    public function __construct(
        protected SharedUserV2Cache $sharedUserCache,
        protected string $yieldmanagerHost
    ) {
        parent::__construct();
    }

    public function deleteAreaFromExpressShifts(
        LocalClock $date,
        string $area
    ): void {
        $this->requestClient()->patch(
            "{$this->yieldmanagerHost}/api/admin/areas/delete-areas-to-express-shifts",
            data: [
                'area' => $area,
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        );
    }

    public function deleteAreaFromShifts(
        LocalClock $date,
        string $area
    ): void {
        $this->requestClient()->patch(
            "{$this->yieldmanagerHost}/api/admin/areas/delete-areas-to-shifts",
            data: [
                'area' => $area,
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        );
    }

    public function deleteOrder(string $orderNumber): void
    {
        $this->requestClient()->delete(
            "{$this->yieldmanagerHost}/api/admin/orders/{$orderNumber}",
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return YMExpressOrder[]
     */
    public function getExpressOrdersFromWarehouseAndDate(string $warehouseId, LocalClock $date): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/express-orders",
            data: [
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMOrder
     */
    public function getOrder(string $orderNumber): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/orders/{$orderNumber}",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMOrder[]
     */
    public function getOrdersFromWarehouseAndDate(string $warehouseId, LocalClock $date): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/orders",
            data: [
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMShift
     */
    public function getShift(string $shiftId): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/shifts/{$shiftId}",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMShift
     */
    public function getShiftFromOrder(string $orderNumber): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/orders/{$orderNumber}/shift",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMShift[]
     */
    public function getShiftsFromDateAndArea(LocalClock $date, string $area): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/shifts-by-date-and-area",
            data: [
                'date' => $date->dateString(),
                'area' => $area,
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMShift[]
     */
    public function getShiftsFromWarehouseAndDate(string $warehouseId, LocalClock $date): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/shifts",
            data: [
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    public function getUser(string $userId): ?User
    {
        if ($this->contextService()->isTest()) {
            return null;
        }

        try {
            $data = $this->requestClient()->get(
                "{$this->yieldmanagerHost}/api/admin/users/{$userId}",
                headers: $this->getHeaders(),
            )->json();
        } catch (RequestException) {
            return null;
        }

        return new User(
            id: $data['id'],
            email: $data['email'],
            name: $data['name'],
            warehouses: $data['warehouses'],
            apiKey: $data['apiKey'],
            role: $data['role'],
            tenantId: $data['tenant']['id'],
            tenant: new Tenant(
                id: $data['tenant']['id'],
                name: $data['tenant']['name'],
                metadata: new TenantMetadata($data['tenant']['metadata']),
                aiAssistantId: $data['tenant']['aiAssistantId'],
                timezone: $data['tenant']['timezone'],
                alias: $data['tenant']['alias'],
                countryCode: $data['tenant']['countryCode'] ?? 'ES',
            ),
            dniNie: $data['dniNie'],
            aiThreadId: $data['aiThreadId'],
        );
    }

    /**
     * @return YMWarehouse
     */
    public function getWarehouse(string $warehouseId): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}",
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMWarehouseResume
     */
    public function getWarehouseResume(string $warehouseId, LocalClock $date): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/resume",
            data: [
                'date' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMWarehouseRule[]
     */
    public function getWarehouseRules(string $warehouseId, LocalClock $date): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/bo/warehouses/{$warehouseId}/rules",
            data: [
                'startDate' => $date->dateString(),
                'endDate' => $date->dateString(),
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return YMWarehouse[]
     */
    public function getWarehouses(): array
    {
        return $this->requestClient()->get(
            "{$this->yieldmanagerHost}/api/admin/warehouses",
            headers: $this->getHeaders(),
        )->json();
    }

    public function openClosesAllShifts(string $warehouseId, LocalClock $date, bool $open): void
    {
        $this->requestClient()->patch(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/{$date->dateString()}/open-close-shifts",
            data: [
                'open' => $open,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function openClosesAllWarehouses(LocalClock $date, bool $open): void
    {
        $this->requestClient()->patch(
            "{$this->yieldmanagerHost}/api/admin/warehouses/open-close-shifts",
            data: [
                'date' => $date->dateString(),
                'open' => $open,
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return YMPing
     */
    public function ping(): array
    {
        return $this->requestClient()->get("{$this->yieldmanagerHost}/ping")->json();
    }

    public function resetPaco(User $user): void
    {
        $this->requestClient()->patch(
            "{$this->yieldmanagerHost}/api/admin/paco/reset",
            data: [
                'userId' => $user->id
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveAllShiftsPrice(string $warehouseId, LocalClock $date, float $price): void
    {
        $this->requestClient()->patch(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/set-prices",
            data: [
                'date' => $date->dateString(),
                'price' => $price,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveAllWarehousesShiftsPrice(LocalClock $date, float $price): void
    {
        $this->requestClient()->patch(
            "{$this->yieldmanagerHost}/api/admin/shifts/set-prices",
            data: [
                'date' => $date->dateString(),
                'price' => $price,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveExpressOrder(
        string $orderNumber,
        ?Address $address = null,
        ?string $shopperCode = null,
        ?int $weight = null,
        ?int $productLines = null,
        ?string $area = null,
    ): void {
        $this->requestClient()->put(
            "{$this->yieldmanagerHost}/api/admin/express-orders/{$orderNumber}",
            data: [
                'address' => $address?->data(null),
                'area' => $area,
                'productLines' => $productLines,
                'shopperCode' => $shopperCode,
                'weight' => $weight,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveExpressOrderStatus(string $orderNumber, string $status): void
    {
        $this->requestClient()->put(
            "{$this->yieldmanagerHost}/api/admin/express-orders/{$orderNumber}/status",
            data: [
                'status' => $status,
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @param string[]|null $tags
     */
    public function saveOrder(
        string $orderNumber,
        ?string $shiftId = null,
        ?Address $address = null,
        ?string $area = null,
        ?string $shopperCode = null,
        ?int $weight = null,
        ?int $productLines = null,
        ?LocalClock $startTime = null,
        ?LocalClock $endTime = null,
        ?bool $returnal = null,
        ?bool $createdByTenant = false,
        ?array $tags = null,
    ): void {
        $this->requestClient()->put(
            "{$this->yieldmanagerHost}/api/admin/orders/{$orderNumber}",
            data: [
                'address' => $address?->data(null),
                'shiftId' => $shiftId,
                'area' => $area,
                'createdByTenant' => $createdByTenant,
                'productLines' => $productLines,
                'returnal' => $returnal,
                'shopperCode' => $shopperCode,
                'timeRange' => $startTime !== null && $endTime !== null
                    ? ['startTime' => $startTime->dateTimeString(), 'endTime' => $endTime->dateTimeString()]
                    : null,
                'weight' => $weight,
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @param string[]|null $warehouses
     */
    public function saveUser(
        string $email,
        ?string $password = null,
        ?string $name = null,
        ?string $role = null,
        ?array $warehouses = null,
        bool $forceSync = false,
    ): void {
        $this->requestClient()->put(
            "{$this->yieldmanagerHost}/api/admin/users",
            data: [
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'password' => $password,
                'warehouses' => $warehouses,
            ],
            headers: $this->getHeaders($forceSync),
        );
    }

    public function saveWarehouseDailyInfoDayOff(
        string $warehouseId,
        LocalClock $date,
        bool $dayOff
    ): void {
        $this->requestClient()->put(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/{$date->dateString()}/day-off",
            data: [
                'dayOff' => $dayOff,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveWarehouseDailyInfoMaxOrders(
        string $warehouseId,
        LocalClock $date,
        ?int $maxOrders
    ): void {
        $this->requestClient()->put(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/{$date->dateString()}/max-orders",
            data: [
                'maxOrders' => $maxOrders,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveWarehouseDailyInfoMaxProductLines(
        string $warehouseId,
        LocalClock $date,
        ?int $maxProductLines
    ): void {
        $this->requestClient()->put(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/{$date->dateString()}/max-product-lines",
            data: [
                'maxProductLines' => $maxProductLines,
            ],
            headers: $this->getHeaders(),
        );
    }

    public function saveWarehouseDailyInfoMaxWeight(
        string $warehouseId,
        LocalClock $date,
        ?int $maxWeight
    ): void {
        $this->requestClient()->put(
            "{$this->yieldmanagerHost}/api/admin/warehouses/{$warehouseId}/{$date->dateString()}/max-weight",
            data: [
                'maxWeight' => $maxWeight,
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function getHeaders(bool $forceSync = false): array
    {
        $tenantId = $this->contextService()->getExtra('tenantId') ?? 'none';
        $apiKey =
            $this->sharedUserCache->read("SYSTEM_{$tenantId}")->apiKey ??
            $this->sharedUserCache->read("ADMIN_{$tenantId}")->apiKey ??
            'none';

        if ($forceSync) {
            return [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'X-Api-Id' => $apiKey,
                'X-Nektria-App' => 'yieldmanager',
                'X-Trace' => $this->contextService()->traceId(),
                'X-Origin' => $this->contextService()->project(),
                'X-Sync' => '1',
            ];
        }

        return [
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
            'X-Api-Id' => $apiKey,
            'X-Nektria-App' => 'yieldmanager',
            'X-Trace' => $this->contextService()->traceId(),
            'X-Origin' => $this->contextService()->project(),
        ];
    }
}
