<?php

declare(strict_types=1);

namespace Nektria\Service;

use Doctrine\ORM\EntityManagerInterface;
use Nektria\Infrastructure\ArrayDocumentReadModel;
use Nektria\Infrastructure\VariableCache;
use Nektria\Util\StringUtil;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

class HealthService
{
    /**
     * @var array<string, string>
     */
    private array $errors;

    public function __construct(
        protected readonly ContextService $contextService,
        protected readonly ContainerInterface $container,
        protected readonly RequestClient $requestClient,
    ) {
        $this->errors = [];
    }

    /**
     * @return array{
     *     errors: array<string, string>,
     *     results: array<string, bool>,
     * }
     */
    public function check(bool $full = false): array
    {
        $this->errors = [];

        if ($full) {
            $data = array_merge(
                $this->checkCompass(),
                $this->checkDatabase(),
                $this->checkMetrics(),
                $this->checkRabbit(),
                $this->checkRedis(),
                $this->checkRouteManager(),
                $this->checkYieldManager(),
                $this->extraChecks(),
            );
        } else {
            $data = array_merge(
                $this->checkDatabase(),
                $this->checkRabbit(),
                $this->checkRedis(),
                $this->extraChecks(),
            );
        }

        return [
            'results' => $data,
            'errors' => $this->errors,
        ];
    }

    public function setContainer(ContainerInterface $container): void
    {
    }

    protected function addError(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    /**
     * @return array<string, bool>
     */
    protected function extraChecks(): array
    {
        return [];
    }

    /**
     * @return array<string, bool>
     */
    private function checkCompass(): array
    {
        if ($this->contextService->project() === 'compass') {
            return [];
        }

        $key = 'compass';
        if (!$this->container->has(CompassClient::class)) {
            return [];
        }

        try {
            /** @var CompassClient $srvc */
            $srvc = $this->container->get(CompassClient::class);
            $srvc->ping();

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkDatabase(): array
    {
        $key = 'database';
        if (!$this->container->has(ArrayDocumentReadModel::class)) {
            return [];
        }

        if (!$this->container->has(EntityManagerInterface::class)) {
            return [];
        }

        try {
            /** @var ArrayDocumentReadModel $srvc */
            $srvc = $this->container->get(ArrayDocumentReadModel::class);
            $srvc->readCustom(
                table: 'doctrine_migration_versions',
                order: 'version',
                page: 1,
                limit: 100,
                filters: [],
            );

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkMetrics(): array
    {
        if ($this->contextService->project() === 'metrics') {
            return [];
        }

        $key = 'metrics';
        if (!$this->container->has(MetricsClient::class)) {
            return [];
        }

        try {
            /** @var MetricsClient $srvc */
            $srvc = $this->container->get(MetricsClient::class);
            $srvc->ping();

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkRabbit(): array
    {
        $key = 'rabbit';
        if (!$this->container->hasParameter('rabbitDsn')) {
            return [];
        }

        /** @var string $rabbitDsn */
        $rabbitDsn = $this->container->getParameter('rabbitDsn');
        $host = str_replace(['amqp', '5672'], ['http', '15672'], $rabbitDsn);

        try {
            $this->requestClient->get("{$host}/api/queues")->json();

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkRedis(): array
    {
        $key = 'redis';
        if (!$this->container->has(VariableCache::class)) {
            return [];
        }

        try {
            $hash = StringUtil::uuid4();
            $value = StringUtil::uuid4();

            /** @var VariableCache $srvc */
            $srvc = $this->container->get(VariableCache::class);
            $srvc->saveString($hash, $value, 5);
            if ($srvc->readString($hash) !== $value) {
                throw new RuntimeException('Failed at read/write operations.');
            }

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkRouteManager(): array
    {
        if ($this->contextService->project() === 'routemanager') {
            return [];
        }

        $key = 'routemanager';
        if (!$this->container->has(RoutemanagerClient::class)) {
            return [];
        }

        try {
            /** @var RoutemanagerClient $srvc */
            $srvc = $this->container->get(RoutemanagerClient::class);
            $srvc->ping();

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }

    /**
     * @return array<string, bool>
     */
    private function checkYieldManager(): array
    {
        if ($this->container->hasParameter('com')) {
            if ($this->contextService->project() === 'yieldmanager') {
                return [];
            }
        }

        $key = 'yieldmanager';
        if (!$this->container->has(YieldmanagerClient::class)) {
            return [];
        }

        try {
            /** @var YieldmanagerClient $srvc */
            $srvc = $this->container->get(YieldmanagerClient::class);
            $srvc->ping();

            return [$key => true];
        } catch (Throwable $e) {
            $this->addError($key, $e->getMessage());

            return [$key => false];
        }
    }
}
