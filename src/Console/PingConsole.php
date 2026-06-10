<?php

declare(strict_types=1);

namespace Nektria\Console;

class PingConsole extends Console
{
    public function __construct()
    {
        parent::__construct('sdk:ping');
    }

    protected function play(): void
    {
    }
}
