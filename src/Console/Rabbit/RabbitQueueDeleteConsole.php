<?php

declare(strict_types=1);

namespace Nektria\Console\Rabbit;

use Nektria\Console\Console;
use Nektria\Service\RequestClient;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

use function is_string;

class RabbitQueueDeleteConsole extends Console
{
    public function __construct(
        private readonly RequestClient $requestClient,
        private readonly ContainerInterface $container,
    ) {
        parent::__construct('sdk:rabbit:delete');
    }

    protected function configure(): void
    {
        $this->addArgument('queue');
    }

    protected function play(): void
    {
        if (!$this->container->hasParameter('rabbitDsn')) {
            throw new RuntimeException('Rabbit not configured.');
        }
        $rabbitDsn = $this->container->getParameter('rabbitDsn');
        if (!is_string($rabbitDsn)) {
            return;
        }
        $this->output()->writeln('Starting queue deletion...');
        $host = str_replace(['amqp', '5672'], ['http', '15672'], $rabbitDsn);
        $targetQueue = $this->input()->getArgument('queue');
        $content = $this->requestClient->get("{$host}/api/queues")->json();
        $this->output()->writeln('Purging ' . $this->input()->getArgument('queue'));

        foreach ($content as $queue) {
            $name = $queue['name'];

            if (str_contains($name, $targetQueue)) {
                try {
                    $vhost = urlencode($queue['vhost']);
                    $this->output()->writeln("Deleting queue '{$name}'...");
                    $this->requestClient->delete(
                        "{$host}/api/queues/{$vhost}/{$name}",
                        data: [
                            'vhost' => '/',
                            'name' => $name,
                            'mode' => 'delete',
                        ],
                    );
                    $this->output()->writeln("Queue '{$name}' deleted.");
                } catch (Throwable) {
                }
            }
        }
    }
}
