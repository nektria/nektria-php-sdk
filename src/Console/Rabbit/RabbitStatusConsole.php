<?php

declare(strict_types=1);

namespace Nektria\Console\Rabbit;

use Nektria\Console\Console;
use Nektria\Infrastructure\SharedVariableCache;
use Nektria\Service\RequestClient;
use Nektria\Util\JsonUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function is_string;

class RabbitStatusConsole extends Console
{
    public function __construct(
        private readonly RequestClient $requestClient,
        private readonly ContainerInterface $container,
        private readonly SharedVariableCache $sharedVariableCache,
    ) {
        parent::__construct('sdk:rabbit:status');
    }

    protected function play(): void
    {
        if (!$this->container->hasParameter('rabbitDsn')) {
            $this->output()->writeln('Rabbit not configured.');

            return;
        }

        $rabbitDsn = $this->container->getParameter('rabbitDsn');
        if (!is_string($rabbitDsn)) {
            return;
        }
        $host = str_replace(['amqp', '5672'], ['http', '15672'], $rabbitDsn);

        $content = $this->requestClient->get(
            "{$host}/api/queues",
        )->json();

        foreach ($content as $queue) {
            $name = str_pad($queue['name'], 35);
            $vhost = $queue['vhost'];
            $ready = str_pad((string) ($queue['messages_ready'] ?? 0), 6);
            $unacked = str_pad((string) ($queue['messages_unacknowledged'] ?? 0), 6);
            $speed = $queue['messages_unacknowledged_details']['rate'] ?? 0;

            $this->output()->writeln("{$vhost} {$name}: R:{$ready} U:{$unacked} S:{$speed}");
        }

        $this->output()->writeln('');
        $this->output()->writeln('PROCESSING:');

        $this->printProcessingMessages();

        $this->output()->writeln('');
        $this->output()->writeln('PENDING:');

        $this->printPendingMessages();
    }

    private function printPendingMessages(): void
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
            sort($clzzs);
            $printHeader = false;
            foreach ($clzzs as $clzz) {
                $times = $this->sharedVariableCache->readInt("bus_messages_pending_{$project}_{$clzz}");
                if ($times > 0) {
                    if (!$printHeader) {
                        $printHeader = true;
                        $this->output()->writeln($project);
                    }

                    $this->output()->writeln("    {$clzz}: {$times}");
                }
            }
        }
    }

    private function printProcessingMessages(): void
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
            sort($clzzs);
            $printHeader = false;
            foreach ($clzzs as $clzz) {
                $times = $this->sharedVariableCache->readInt("bus_messages_{$project}_{$clzz}");
                if ($times > 0) {
                    if (!$printHeader) {
                        $printHeader = true;
                        $this->output()->writeln($project);
                    }

                    $this->output()->writeln("    {$clzz}: {$times}");
                }
            }
        }
    }
}
