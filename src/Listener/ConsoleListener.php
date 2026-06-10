<?php

declare(strict_types=1);

namespace Nektria\Listener;

use Nektria\Console\Console;
use Nektria\Document\ThrowableDocument;
use Nektria\Infrastructure\VariableCache;
use Nektria\Service\AlertService;
use Nektria\Service\ContextService;
use Nektria\Service\LogService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly abstract class ConsoleListener implements EventSubscriberInterface
{
    public function __construct(
        private AlertService $alertService,
        private ContextService $contextService,
        private LogService $logService,
        private VariableCache $variableCache,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => 'onConsoleError',
        ];
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $command = $event->getCommand();
        $this->contextService->setContext(ContextService::SYSTEM);

        if ($command instanceof Console || $command === null) {
            return;
        }

        $path = '/Console/' . str_replace('\\', '/', $command::class);

        $this->logService->temporalLogs();
        $this->logService->exception($event->getError(), []);

        if ($this->contextService->env() === ContextService::DEV || $this->variableCache->refreshKey($path)) {
            $tenantName = 'none';

            $args = $event->getInput()->getArguments();
            $options = $event->getInput()->getOptions();

            $method = 'CONSOLE';
            $this->alertService->sendThrowable(
                $tenantName,
                $method,
                $path,
                [
                    'command' => $args['command'],
                    'arguments' => $args['receivers'] ?? [],
                    'options' => $options,
                ],
                new ThrowableDocument($event->getError()),
            );
        }
    }
}
