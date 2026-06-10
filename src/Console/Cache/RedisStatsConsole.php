<?php

declare(strict_types=1);

namespace Nektria\Console\Cache;

use Nektria\Console\Console;
use Nektria\Infrastructure\RedisCache;
use Nektria\Service\AlertService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RedisStatsConsole extends Console
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly AlertService $alertService
    ) {
        parent::__construct('sdk:cache:stats');
    }

    protected function play(): void
    {
        if (!method_exists($this->container, 'getServiceIds')) {
            return;
        }

        $ids = $this->container->getServiceIds();
        $total = 0;

        $lines = 0;
        $output = '';

        foreach ($ids as $id) {
            if (str_ends_with($id, 'Cache')) {
                $cache = $this->container->get($id);
                if (!$cache instanceof RedisCache) {
                    continue;
                }
                $class = $cache::class;
                $stat = $cache->size();
                $size = $stat[0];
                $mem = round($stat[1] / (1024 * 1024), 3);
                $this->output()->writeln(
                    "[{$class}] <green>{$cache->fqn}:</green> {$size} (<yellow1>{$mem} MB</yellow1>)"
                );
                $total += $mem;

                $output .= "[{$class}] {$cache->fqn} {$size} ({$mem} MB)" . AlertService::EMPTY_LINE;

                ++$lines;

                if ($lines === 10) {
                    $this->alertService->simpleMessage(
                        AlertService::CHANNEL_BUGS,
                        $output
                    );
                    $output = '';
                    $lines = 0;
                }
            }
        }

        $output .= "Total: {$total} MB";
        $this->alertService->simpleMessage(
            AlertService::CHANNEL_BUGS,
            $output
        );

        $this->output()->writeln("<green>Total:</green> {$total} MB");
    }
}
