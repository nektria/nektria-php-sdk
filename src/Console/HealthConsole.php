<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Service\HealthService;
use Nektria\Util\JsonUtil;
use RuntimeException;

use function count;

class HealthConsole extends Console
{
    public function __construct(
        private readonly HealthService $healthService
    ) {
        parent::__construct('sdk:health');
    }

    protected function play(): void
    {
        $data = $this->healthService->check();
        ksort($data['results']);

        foreach ($data['results'] as $key => $result) {
            if ($result) {
                $this->output()->writeln(" - {$key}: <green>OK</green>");
            } else {
                $this->output()->writeln(" - {$key}: <red>FAIL</red>");
            }
        }

        if (count($data['errors']) > 0) {
            $this->output()->writeln('');
        }

        foreach ($data['errors'] as $key => $error) {
            $this->output()->writeln(" - <error>{$key}</error>: <red>{$error}</red>");
        }

        if (count($data['errors']) > 0) {
            $jsonData = JsonUtil::encode($data);

            throw new RuntimeException("Health failed. ({$jsonData})");
        }
    }
}
