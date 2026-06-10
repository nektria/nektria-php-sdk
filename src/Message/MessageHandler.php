<?php

declare(strict_types=1);

namespace Nektria\Message;

use Nektria\Document\Tenant;
use Nektria\Document\User;
use Nektria\Exception\InsufficientCredentialsException;
use Nektria\Infrastructure\SecurityServiceInterface;
use Nektria\Service\ProcessRegistry;
use Nektria\Service\RoleManager;
use Nektria\Util\ContainerBox;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function count;
use function define;
use function in_array;

define('MESSAGE_HANDLER_CONTAINER_BOX', new ContainerBox());

readonly abstract class MessageHandler
{
    public const ContainerBox CONTAINER_BOX = MESSAGE_HANDLER_CONTAINER_BOX;

    public function inject(ContainerInterface $container): void
    {
        self::CONTAINER_BOX->set($container);
    }

    protected function checkAccessToTenant(string $tenantId): void
    {
        if ($tenantId !== $this->tenant()->id) {
            throw new InsufficientCredentialsException();
        }
    }

    protected function checkAccessToWarehouse(string $warehouseId): void
    {
        if (!$this->hasAccessToWarehouse($warehouseId)) {
            throw new InsufficientCredentialsException();
        }
    }

    protected function containerBox(): ContainerBox
    {
        return self::CONTAINER_BOX;
    }

    protected function hasAccessToWarehouse(string $warehouseId): bool
    {
        $user = $this->userService()->retrieveCurrentUser();

        if ($user->role === RoleManager::ROLE_DRIVER && count($user->warehouses) === 0) {
            return false;
        }

        if (count($user->warehouses) === 0) {
            return true;
        }

        if ($this->roleManager()->canAtLeast($user->role, [RoleManager::ROLE_ADMIN])) {
            return true;
        }

        if (!in_array($warehouseId, $user->warehouses, true)) {
            return false;
        }

        return true;
    }

    protected function registry(): ProcessRegistry
    {
        return self::CONTAINER_BOX->get(ProcessRegistry::class);
    }

    protected function tenant(): Tenant
    {
        return $this->userService()->retrieveCurrentUser()->tenant;
    }

    protected function tenantId(): string
    {
        return $this->userService()->retrieveCurrentUser()->tenantId;
    }

    protected function user(): User
    {
        return $this->userService()->retrieveCurrentUser();
    }

    protected function userService(): SecurityServiceInterface
    {
        return self::CONTAINER_BOX->get(SecurityServiceInterface::class);
    }

    private function roleManager(): RoleManager
    {
        return self::CONTAINER_BOX->get(RoleManager::class);
    }
}
