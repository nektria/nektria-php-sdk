<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Exception\RequestException;
use Nektria\Infrastructure\SharedUserV2Cache;

use function in_array;

readonly class ProxyClient extends AbstractService
{
    public function __construct(
        protected SharedUserV2Cache $sharedUserCache,
    ) {
        parent::__construct();
    }

    public function assignPlatforms(string $pickingShiftId): void
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient()->patch(
            "{$proxyHost}/api/admin/picking-shifts/{$pickingShiftId}/assign-platforms",
            headers: $this->getHeaders(),
        );
    }

    public function executeTask(string $task): void
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient()->get(
            "{$proxyHost}/api/admin/tasks/{$task}",
            headers: $this->getHeaders(),
        );
    }

    public function getBillingFile(): string
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return '';
        }

        return $this->requestClient()->get(
            "{$proxyHost}/api/admin/files/billing",
            headers: $this->getHeaders(),
        )->body;
    }

    public function importOrdersFromFile(): void
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient()->post(
            "{$proxyHost}/api/admin/orders/import",
            headers: $this->getHeaders(),
        );
    }

    public function sendOrderIsCreatedInRouteManager(string $orderNumber): void
    {
        if ($this->contextService()->isTest()) {
            return;
        }

        if (!$this->pathIsAllowed('on_rm_order_created')) {
            return;
        }

        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        $this->requestClient()->patch(
            "{$proxyHost}/api/admin/routemanager/orders/{$orderNumber}/created",
            headers: $this->getHeaders(),
        );
    }

    public function sendOrderIsUpdated(string $orderNumber): void
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient()->patch(
            "{$proxyHost}/api/admin/orders/{$orderNumber}/updated",
            headers: $this->getHeaders(),
        );
    }

    public function sendOrderStatusIsDelivered(string $orderNumber): void
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient()->patch(
            "{$proxyHost}/api/admin/orders/{$orderNumber}/delivered",
            headers: $this->getHeaders(),
        );
    }

    public function sendOrderStatusUpdated(string $orderNumber, string $status): void
    {
        $status = strtolower($status);

        if ($status === 'cancelled') {
            $status = 'canceled';
        }

        if (!$this->pathIsAllowed("/api/admin/orders/{orderNumber}/status/{$status}")) {
            return;
        }

        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost() ?? '';

        $this->requestClient()->put(
            "{$proxyHost}/api/admin/orders/{$orderNumber}/status/{$status}",
            headers: $this->getHeaders(),
        );
    }

    public function sendPickingShiftRoutes(string $pickingShiftId): void
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        try {
            $this->requestClient()->patch(
                "{$proxyHost}/api/admin/picking-shifts/{$pickingShiftId}/send-routes",
                headers: $this->getHeaders(),
            );
        } catch (RequestException) {
            // do nothing
        }
    }

    public function sendRouteIsUpdated(string $routeId): void
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient()->patch(
            "{$proxyHost}/api/admin/routes/{$routeId}/updated",
            headers: $this->getHeaders(),
        );
    }

    public function sendWarehouseAreasUpdated(string $warehouseId): void
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        if (!$this->pathIsAllowed('/api/admin/warehouses/{warehouseId}/areas-updated')) {
            return;
        }

        $this->requestClient()->patch(
            "{$proxyHost}/api/admin/warehouses/{$warehouseId}/areas-updated",
            headers: $this->getHeaders(),
        );
    }

    public function uploadOrdersFileToPickingShift(string $pickingShiftId, string $filename): void
    {
        $proxyHost = $this->securityService()->retrieveCurrentUser()->tenant->metadata->proxyHost();

        if ($proxyHost === null) {
            return;
        }

        $this->requestClient()->files(
            "{$proxyHost}/api/admin/files/picking-shifts/{$pickingShiftId}/orders",
            filenames: [
                'ordersFile' => $filename
            ],
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return array<string, string>
     */
    private function getHeaders(): array
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
            'X-Nektria-App' => 'nektria-proxy',
            'X-Trace' => $this->contextService()->traceId(),
            'X-Origin' => $this->contextService()->project(),
        ];
    }

    private function pathIsAllowed(string $path): bool
    {
        $tenant = $this->securityService()->retrieveCurrentTenant();
        $validPaths = $tenant->metadata->getField('proxyPaths') ?? [];

        return in_array($path, $validPaths, true);
    }
}
