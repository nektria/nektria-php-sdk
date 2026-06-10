<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\BusInterface;
use Nektria\Infrastructure\SecurityServiceInterface;
use Nektria\Util\ContainerBox;
use Symfony\Component\DependencyInjection\ContainerInterface;

readonly abstract class AbstractService
{
    protected ContainerBox $serviceContainer;

    public function __construct()
    {
        $this->serviceContainer = new ContainerBox();
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->serviceContainer->set($container);
    }

    protected function alertService(): AlertService
    {
        /** @var AlertService $service */
        $service = $this->serviceContainer->get(AlertService::class);

        return $service;
    }

    protected function bus(): BusInterface
    {
        /** @var BusInterface $service */
        $service = $this->serviceContainer->get(BusInterface::class);

        return $service;
    }

    protected function compassClient(): CompassClient
    {
        /** @var CompassClient $service */
        $service = $this->serviceContainer->get(CompassClient::class);

        return $service;
    }

    protected function contextService(): ContextService
    {
        /** @var ContextService $service */
        $service = $this->serviceContainer->get(ContextService::class);

        return $service;
    }

    protected function googleClient(): GoogleClient
    {
        /** @var GoogleClient $service */
        $service = $this->serviceContainer->get(GoogleClient::class);

        return $service;
    }

    protected function logService(): LogService
    {
        /** @var LogService $service */
        $service = $this->serviceContainer->get(LogService::class);

        return $service;
    }

    protected function registry(): ProcessRegistry
    {
        /** @var ProcessRegistry $service */
        $service = $this->serviceContainer->get(ProcessRegistry::class);

        return $service;
    }

    protected function requestClient(): RequestClient
    {
        /** @var RequestClient $service */
        $service = $this->serviceContainer->get(RequestClient::class);

        return $service;
    }

    protected function roleManager(): RoleManager
    {
        /** @var RoleManager $service */
        $service = $this->serviceContainer->get(RoleManager::class);

        return $service;
    }

    protected function routemanagerClient(): RoutemanagerClient
    {
        /** @var RoutemanagerClient $service */
        $service = $this->serviceContainer->get(RoutemanagerClient::class);

        return $service;
    }

    protected function securityService(): SecurityServiceInterface
    {
        /** @var SecurityServiceInterface $service */
        $service = $this->serviceContainer->get(SecurityServiceInterface::class);

        return $service;
    }

    protected function socketService(): SocketService
    {
        /** @var SocketService $service */
        $service = $this->serviceContainer->get(SocketService::class);

        return $service;
    }

    protected function yieldmanagerClient(): YieldmanagerClient
    {
        /** @var YieldmanagerClient $service */
        $service = $this->serviceContainer->get(YieldmanagerClient::class);

        return $service;
    }
}
