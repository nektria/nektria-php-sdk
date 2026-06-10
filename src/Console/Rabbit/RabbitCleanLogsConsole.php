<?php

declare(strict_types=1);

namespace Nektria\Console\Rabbit;

use Nektria\Console\Console;
use Nektria\Infrastructure\SharedVariableCache;
use Nektria\Util\JsonUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RabbitCleanLogsConsole extends Console
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly SharedVariableCache $sharedVariableCache,
    ) {
        parent::__construct('sdk:rabbit:clean-logs');
    }

    protected function play(): void
    {
        if (!$this->container->hasParameter('rabbitDsn')) {
            $this->output()->writeln('Rabbit not configured.');

            return;
        }

        $this->cleanProcessingMessages();
        $this->cleanPendingMessages();
    }

    private function cleanPendingMessages(): void
    {
        $data = JsonUtil::decode($this->sharedVariableCache->readString('bus_messages_pending', '[]'));

        $projects = [];
        foreach ($data as $item) {
            [$project, $clzz] = explode('_', $item);

            $projects[$project] ??= [];
            $projects[$project][] = $clzz;
        }

        ksort($projects);
        foreach ($projects as $project => $clzzs) {
            foreach ($clzzs as $clzz) {
                $this->sharedVariableCache->saveInt("bus_messages_pending_{$project}_{$clzz}", 0, ttl: 3600);
            }
        }
    }

    private function cleanProcessingMessages(): void
    {
        $data = JsonUtil::decode($this->sharedVariableCache->readString('bus_messages', '[]'));

        $projects = [];
        foreach ($data as $item) {
            [$project, $clzz] = explode('_', $item);

            $projects[$project] ??= [];
            $projects[$project][] = $clzz;
        }

        ksort($projects);
        foreach ($projects as $project => $clzzs) {
            foreach ($clzzs as $clzz) {
                $this->sharedVariableCache->saveInt("bus_messages_{$project}_{$clzz}", 0, ttl: 3600);
            }
        }
    }
}
