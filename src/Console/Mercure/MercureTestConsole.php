<?php

declare(strict_types=1);

namespace Nektria\Console\Mercure;

use Nektria\Console\Console;
use Nektria\Document\ArrayDocument;
use Nektria\Service\ContextService;
use Nektria\Service\SocketService;
use Symfony\Component\Console\Input\InputArgument;

class MercureTestConsole extends Console
{
    public function __construct(
        private readonly SocketService $socketService,
        private readonly ContextService $contextService,
    ) {
        parent::__construct('admin:mercure:test');
    }

    protected function configure(): void
    {
        $this->addArgument('tenant', InputArgument::REQUIRED);
    }

    protected function play(): void
    {
        $this->userService()->authenticateSystem($this->readArgument('tenant'));

        $this->socketService->publishToTenant('mercure.test', new ArrayDocument([
            'project' => $this->contextService->project(),
            'message' => 'Hello'
        ]));
    }
}
