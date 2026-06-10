<?php

declare(strict_types=1);

namespace Nektria\Console\Debug;

use Nektria\Console\Console;
use Nektria\Exception\RequestException;
use Nektria\Service\GoogleClient;
use Throwable;

class TestGeminiConsole extends Console
{
    public function __construct(
        private readonly GoogleClient $googleClient
    ) {
        parent::__construct('debug:gemini');
    }

    protected function play(): void
    {
        try {
            $this->output()->writeln($this->googleClient->ask());
        } catch (RequestException $e) {
            // ump($e->response()->json());
        } catch (Throwable $e) {
            // ump($e->getMessage());
        }
    }
}
