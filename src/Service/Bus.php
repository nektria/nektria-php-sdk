<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\Document;
use Nektria\Dto\ArrayContainer;
use Nektria\Exception\NektriaException;
use Nektria\Infrastructure\BusInterface;
use Nektria\Message\Command;
use Nektria\Message\Event;
use Nektria\Message\Query;
use Nektria\Util\Annotation\RolesRequired;
use Nektria\Util\MessageStamp\ContextStamp;
use Nektria\Util\MessageStamp\RetryStamp;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Throwable;

use function count;
use function in_array;

readonly class Bus extends AbstractService implements BusInterface
{
    /**
     * @var ArrayContainer<array{
     *     event: Event,
     *     transport: string|null,
     *     delayMs: int|null,
     *     retryOptions: array{
     *         currentTry?: int,
     *         maxTries: int,
     *         interval: int,
     *     }|null
     * }>
     */
    private ArrayContainer $delayedEvents;

    public function __construct(
        private MessageBusInterface $bus,
    ) {
        parent::__construct();

        $this->delayedEvents = new ArrayContainer();
    }

    /**
     * @param array{
     *     currentTry?: int,
     *     maxTries: int,
     *     interval: int,
     * }|null $retryOptions
     */
    final public function addDelayedEvent(
        Event $event,
        ?string $transport = null,
        ?int $delayMs = null,
        ?array $retryOptions = null
    ): void {
        $this->dispatchEvent($event, $transport, $delayMs, $retryOptions);
    }

    /**
     * @param array{
     *     currentTry?: int,
     *     maxTries: int,
     *     interval: int,
     * }|null $retryOptions
     * @throws Throwable
     */
    final public function dispatchCommand(
        Command $command,
        ?string $transport = null,
        ?int $delayMs = null,
        ?array $retryOptions = null
    ): void {
        $this->validateAccess($command);

        $stamps = [
            new ContextStamp(
                traceId: $this->contextService()->traceId(),
                context: $this->contextService()->context(),
                tenantId: $this->contextService()->getExtra('tenantId'),
                userId: $this->contextService()->getExtra('userId'),
            ),
        ];

        if ($transport !== null && !$this->contextService()->forceSync()) {
            $stamps[] = new TransportNamesStamp([$transport]);
        }

        if ($this->contextService()->forceSync()) {
            $stamps[] = new TransportNamesStamp(['sync']);
        }

        if ($retryOptions !== null) {
            if ($this->contextService()->env() === ContextService::DEV) {
                $stamps[] = new RetryStamp(
                    max(1, $retryOptions['currentTry'] ?? 1),
                    min(10, $retryOptions['maxTries']),
                    min(10_000, $retryOptions['interval']),
                );
            } else {
                $stamps[] = new RetryStamp(
                    max(1, $retryOptions['currentTry'] ?? 1),
                    $retryOptions['maxTries'],
                    $retryOptions['interval'],
                );
            }
        }

        if ($delayMs !== null) {
            $stamps[] = new DelayStamp($delayMs);
        }

        try {
            $this->bus->dispatch($command, $stamps);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous !== null) {
                throw $previous;
            }

            throw $e;
        }
    }

    final public function dispatchDelayedEvents(): void
    {
        foreach ($this->delayedEvents->list() as $event) {
            try {
                $this->dispatchEvent(
                    $event['event'],
                    transport: $event['transport'],
                    delayMs: $event['delayMs'],
                    retryOptions: $event['retryOptions'],
                );
            } catch (Throwable) {
            }
        }
        $this->delayedEvents->empty();
    }

    /**
     * @param array{
     *     currentTry?: int,
     *     maxTries: int,
     *     interval: int,
     * }|null $retryOptions
     */
    final public function dispatchEvent(
        Event $event,
        ?string $transport = null,
        ?int $delayMs = null,
        ?array $retryOptions = null
    ): void {
        $stamps = [
            new ContextStamp(
                traceId: $this->contextService()->traceId(),
                context: $this->contextService()->context(),
                tenantId: $this->contextService()->getExtra('tenantId'),
                userId: $this->contextService()->getExtra('userId'),
            ),
        ];

        if ($transport !== null && !$this->contextService()->forceSync()) {
            $stamps[] = new TransportNamesStamp([$transport]);
        }

        if ($this->contextService()->forceSync()) {
            $stamps[] = new TransportNamesStamp(['sync']);
        }

        if ($retryOptions !== null) {
            if ($this->contextService()->env() === ContextService::DEV) {
                $stamps[] = new RetryStamp(
                    max(1, $retryOptions['currentTry'] ?? 1),
                    min(10, $retryOptions['maxTries']),
                    min(10_000, $retryOptions['interval']),
                );
            } else {
                $stamps[] = new RetryStamp(
                    max(1, $retryOptions['currentTry'] ?? 1),
                    $retryOptions['maxTries'],
                    $retryOptions['interval'],
                );
            }
        }

        if ($delayMs !== null) {
            $stamps[] = new DelayStamp($delayMs);
        }

        try {
            $this->bus->dispatch($event, $stamps);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous !== null) {
                throw NektriaException::new($previous);
            }

            throw NektriaException::new($e);
        }
    }

    /**
     * @template T of Document
     * @param Query<T> $query
     * @return T
     * @throws Throwable
     */
    final public function dispatchQuery(Query $query): Document
    {
        try {
            $this->validateAccess($query);

            $result = $this->bus->dispatch($query, [
                new ContextStamp(
                    traceId: $this->contextService()->traceId(),
                    context: $this->contextService()->context(),
                    tenantId: $this->contextService()->getExtra('tenantId'),
                    userId: $this->contextService()->getExtra('userId'),
                ),
            ])->last(HandledStamp::class);

            if ($result === null) {
                throw new RuntimeException('Query does not return a Document');
            }

            return $result->getResult();
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous !== null) {
                throw $previous;
            }

            throw $e;
        }
    }

    /**
     * @template T of Document
     * @param Command|Query<T> $message
     */
    private function validateAccess(Command | Query $message): void
    {
        try {
            $reflectionClass = new ReflectionClass($message);
            $attributes = $reflectionClass->getAttributes(RolesRequired::class);

            if (count($attributes) === 0) {
                $clzz = $reflectionClass->getName();

                throw new RuntimeException("Query '{$clzz}' does not have RolesRequired attribute");
            }

            /** @var RolesRequired $instance */
            $instance = $attributes[0]->newInstance();

            if (in_array(RoleManager::ROLE_PUBLIC, $instance->roles, true)) {
                return;
            }

            if (in_array(RoleManager::ROLE_ANY, $instance->roles, true)) {
                return;
            }

            $this->securityService()->validateRole($instance->roles);
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }
}
