<?php

declare(strict_types=1);

namespace Nektria\Listener;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use DomainException;
use Nektria\Document\Document;
use Nektria\Document\Tenant;
use Nektria\Document\ThrowableDocument;
use Nektria\Dto\Clock;
use Nektria\Exception\ResourceNotFoundException;
use Nektria\Infrastructure\BusInterface;
use Nektria\Infrastructure\SecurityServiceInterface;
use Nektria\Infrastructure\SharedVariableCache;
use Nektria\Infrastructure\VariableCache;
use Nektria\Message\Command;
use Nektria\Message\Event;
use Nektria\Message\Query;
use Nektria\Service\AlertService;
use Nektria\Service\ContextService;
use Nektria\Service\LogService;
use Nektria\Service\ProcessRegistry;
use Nektria\Util\JsonUtil;
use Nektria\Util\MessageStamp\ContextStamp;
use Nektria\Util\MessageStamp\RetryStamp;
use Nektria\Util\StringUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Throwable;

use function in_array;

abstract class MessageListener implements EventSubscriberInterface
{
    public const string LOG_LEVEL_DEBUG = 'DEBUG';

    public const string LOG_LEVEL_INFO = 'INFO';

    public const string LOG_LEVEL_NONE = 'NONE';

    private float $executionTime;

    /** @var Query<Document>|Command|Event|null */
    private Query | Command | Event | null $message;

    private string $messageCompletedAt;

    private string $messageStartedAt;

    public function __construct(
        private readonly AlertService $alertService,
        private readonly BusInterface $bus,
        private readonly ContextService $contextService,
        private readonly LogService $logService,
        private readonly ProcessRegistry $processRegistry,
        private readonly SecurityServiceInterface $securityService,
        private readonly SharedVariableCache $sharedVariableCache,
        private readonly VariableCache $variableCache,
    ) {
        $this->executionTime = microtime(true);
        $this->messageCompletedAt = Clock::now()->iso8601String();
        $this->messageStartedAt = $this->messageCompletedAt;
        $this->message = null;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SendMessageToTransportsEvent::class => 'onSendMessageToTransports',
            WorkerMessageReceivedEvent::class => 'onWorkerMessageReceived',
            WorkerMessageHandledEvent::class => 'onWorkerMessageHandled',
            WorkerMessageFailedEvent::class => 'onMessengerException',
            WorkerStoppedEvent::class => 'onWorkerStoppedEvent',
        ];
    }

    public function onMessengerException(WorkerMessageFailedEvent $event): void
    {
        try {
            $retryStamp = $event->getEnvelope()->last(RetryStamp::class);
            $transportStamp = $event->getEnvelope()->last(TransportNamesStamp::class);
            $this->messageCompletedAt = Clock::now()->iso8601String();
            $message = $event->getEnvelope()->getMessage();
            $maxRetries = 1;

            $this->message = null;
            if ($message instanceof Command || $message instanceof Event || $message instanceof Query) {
                $this->decreaseCounter($message);
            }

            if ($retryStamp !== null) {
                $maxRetries = $retryStamp->maxRetries;
                $nextTry = $retryStamp->currentTry + 1;
                if ($nextTry <= $retryStamp->maxRetries) {
                    $transport = null;
                    if ($transportStamp !== null) {
                        $transport = $transportStamp->getTransportNames()[0];
                    }

                    if ($message instanceof Command) {
                        $this->bus->dispatchCommand(
                            $message,
                            transport: $transport,
                            delayMs: $retryStamp->intervalMs,
                            retryOptions: [
                                'currentTry' => $nextTry,
                                'maxTries' => $retryStamp->maxRetries,
                                'interval' => $retryStamp->intervalMs,
                            ],
                        );
                    } elseif ($message instanceof Event) {
                        $this->bus->dispatchEvent($message);
                    }
                }
            }

            if ($message instanceof Command || $message instanceof Event) {
                $encoders = [new JsonEncoder()];
                $normalizers = [new PropertyNormalizer(), new DateTimeNormalizer(), new ObjectNormalizer()];
                $serializer = new Serializer($normalizers, $encoders);
                $data = JsonUtil::decode($serializer->serialize($message, 'json'));
                $exception = $event->getThrowable();
                $class = $message::class;
                $classHash = str_replace('\\', '_', $class);
                $messageClass = StringUtil::className($message);
                $silent = false;

                if ($exception instanceof HandlerFailedException && $exception->getPrevious() !== null) {
                    $exception = $exception->getPrevious();
                }

                if (!$exception instanceof ThrowableDocument) {
                    $exception = new ThrowableDocument($exception);
                    $silent = $exception->silent;
                }

                $originalException = $exception->throwable;
                if (
                    $originalException instanceof DriverException
                    || $originalException instanceof ConnectionException
                ) {
                    exit(1);
                }

                $exchangeName = '?';
                $exchangeStamp = $event->getEnvelope()->last(AmqpReceivedStamp::class);
                if ($exchangeStamp !== null) {
                    $exchangeName = $exchangeStamp->getAmqpEnvelope()->getExchangeName();
                }

                if (isset($data['user']['email'])) {
                    $data['user']['email'] = '******';
                }

                if (isset($data['user']['dniNie'])) {
                    $data['user']['dniNie'] = '******';
                }

                if (isset($data['oldUuser']['email'])) {
                    $data['user']['email'] = '******';
                }

                if (isset($data['oldUser']['dniNie'])) {
                    $data['user']['dniNie'] = '******';
                }

                $this->logService->temporalLogs();
                $this->logService->exception(
                    exception: $originalException,
                    extra: [
                        'context' => 'messenger',
                        'role' => $this->contextService->context(),
                        'code' => $this->normalizeClass($class),
                        'body' => $data,
                        'messageReceivedAt' => $this->messageStartedAt,
                        'messageCompletedAt' => $this->messageCompletedAt,
                        'queue' => $exchangeName,
                        'maxRetries' => $maxRetries,
                        'httpRequest' => [
                            'requestUrl' => "/{$messageClass}/{$message->ref()}",
                            'requestMethod' => 'QUEUE',
                            'status' => 500,
                            'latency' => max(0.001, round(microtime(true) - $this->executionTime, 3)) . 's',
                        ],
                    ]
                );

                $tenantName = $this->securityService->currentUser()?->tenant->name ?? 'none';

                $key = "{$tenantName}-messenger-{$classHash}";
                $key2 = "{$tenantName}-messenger-{$classHash}_count";
                if ($this->contextService->env() === ContextService::DEV || $this->variableCache->refreshKey($key)) {
                    $ignoreMessages = [
                        'Redelivered message from AMQP detected that will be rejected and trigger the retry logic.',
                    ];

                    $times = $this->variableCache->readInt($key2, 1);

                    if (!in_array($originalException->getMessage(), $ignoreMessages, true)) {
                        $sendAlert = true;
                        if (
                            $originalException instanceof ResourceNotFoundException
                            || $originalException instanceof DomainException
                        ) {
                            $sendAlert = false;
                        }

                        if ($sendAlert && !$silent) {
                            $this->alertService->sendThrowable(
                                $this->securityService->currentUser()?->tenant->name ?? 'none',
                                'RABBIT ' . $originalException::class . ' ' . $exception::class,
                                "/{$messageClass}/{$message->ref()}",
                                $data,
                                $exception,
                                $times,
                            );
                        }
                    }

                    $this->variableCache->saveInt($key2, 0);
                } else {
                    $times = $this->variableCache->readInt($key2);
                    $this->variableCache->saveInt($key2, $times + 1);
                }
            }

            $this->securityService->clearAuthentication();
            $this->processRegistry->clear();
            $this->cleanMemory();

            gc_collect_cycles();
        } catch (Throwable) {
        }
    }

    public function onSendMessageToTransports(SendMessageToTransportsEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof Command || $message instanceof Event || $message instanceof Query) {
            $this->increasePendingCounter($message);
        }
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $this->bus->dispatchDelayedEvents();
        $this->messageCompletedAt = Clock::now()->iso8601String();
        $message = $event->getEnvelope()->getMessage();

        $this->message = null;
        if ($message instanceof Command || $message instanceof Event || $message instanceof Query) {
            $this->decreaseCounter($message);
        }

        $exchangeName = '?';
        $exchangeStamp = $event->getEnvelope()->last(AmqpReceivedStamp::class);
        if ($exchangeStamp !== null) {
            $exchangeName = $exchangeStamp->getAmqpEnvelope()->getExchangeName() ?? '?';
        }

        $logLevel = $this->assignLogLevel(
            $this->normalizeClass($message::class),
            $this->securityService->currentUser()?->tenant
        );

        if ($logLevel === null) {
            if (str_ends_with($exchangeName, '.system')) {
                $logLevel = self::LOG_LEVEL_DEBUG;
            } else {
                $logLevel = self::LOG_LEVEL_INFO;
            }
        }

        if (
            $logLevel !== self::LOG_LEVEL_NONE
            && ($message instanceof Command || $message instanceof Event)
        ) {
            $encoders = [new JsonEncoder()];
            $normalizers = [new PropertyNormalizer(), new DateTimeNormalizer(), new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $data = JsonUtil::decode($serializer->serialize($message, 'json'));
            $messageClass = StringUtil::className($message);
            $resume = "/{$messageClass}/{$message->ref()}";
            $time = max(0.001, round(microtime(true) - $this->executionTime, 3)) . 's';

            $this->processRegistry->addValue('context', 'messenger');
            $this->processRegistry->addValue('path', $this->normalizeClass($message::class));
            $this->processRegistry->addValue('queue', $exchangeName);

            if ($logLevel === self::LOG_LEVEL_DEBUG) {
                $this->logService->debug([
                    'body' => $data,
                    'executionTime' => $time,
                    'httpRequest' => [
                        'requestUrl' => $resume,
                        'requestMethod' => 'QUEUE',
                        'status' => 200,
                        'latency' => $time,
                    ],
                    'messageReceivedAt' => $this->messageStartedAt,
                    'messageCompletedAt' => $this->messageCompletedAt,
                ], $resume);
            } else {
                $this->logService->info([
                    'body' => $data,
                    'executionTime' => $time,
                    'httpRequest' => [
                        'requestUrl' => $resume,
                        'requestMethod' => 'QUEUE',
                        'status' => 200,
                        'latency' => $time,
                    ],
                    'messageReceivedAt' => $this->messageStartedAt,
                    'messageCompletedAt' => $this->messageCompletedAt,
                ], $resume);
            }
        }
        $this->securityService->clearAuthentication();
        $this->processRegistry->clear();

        $this->cleanMemory();

        gc_collect_cycles();
    }

    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        $exchangeName = '?';
        $exchangeStamp = $event->getEnvelope()->last(AmqpReceivedStamp::class);
        if ($exchangeStamp !== null) {
            $exchangeName = $exchangeStamp->getAmqpEnvelope()->getExchangeName() ?? '?';
        }

        $this->processRegistry->getMetadata()->updateField('context', 'messenger');
        $this->processRegistry->getMetadata()->updateField('path', $this->normalizeClass($message::class));
        $this->processRegistry->getMetadata()->updateField('queue', $exchangeName);

        $message = $event->getEnvelope()->getMessage();
        if ($message instanceof Command || $message instanceof Event || $message instanceof Query) {
            $this->message = $message;
            $this->increaseCounter($message);
            $this->decreasePendingCounter($message);
        }

        try {
            /** @var ContextStamp|null $contextStamp */
            $contextStamp = $event->getEnvelope()->last(ContextStamp::class);

            if ($contextStamp !== null) {
                $this->contextService->setContext($contextStamp->context);
                $this->contextService->setTraceId($contextStamp->traceId);
                if ($contextStamp->tenantId !== null) {
                    $this->securityService->authenticateSystem($contextStamp->tenantId);
                }
                $this->contextService->addExtra('userId', $contextStamp->userId);
                $this->contextService->addExtra('tenantId', $contextStamp->tenantId);
            }

            $this->messageStartedAt = Clock::now()->iso8601String();
            $this->executionTime = microtime(true);
        } catch (Throwable $e) {
            $this->alertService->sendThrowable(
                $this->securityService->currentUser()?->tenant->name ?? 'none',
                'RABBIT',
                '',
                [],
                new ThrowableDocument($e),
            );

            if ($e instanceof DriverException) {
                throw $e;
            }
        }
    }

    public function onWorkerStoppedEvent(): void
    {
        if ($this->message !== null) {
            $this->decreaseCounter($this->message);
        }
        $this->cleanMemory();

        gc_collect_cycles();
    }

    protected function assignLogLevel(string $code, ?Tenant $tenant): ?string
    {
        return null;
    }

    protected function cleanMemory(): void
    {
    }

    /**
     * @param Event|Query<Document>|Command $message
     */
    private function decreaseCounter(Event | Query | Command $message): void
    {
        $project = $this->contextService->project();
        $clzz = $message::class;
        $data = JsonUtil::decode($this->sharedVariableCache->readString('bus_messages', '[]'));
        $key = "{$project}_{$clzz}";
        if (!in_array($key, $data, true)) {
            $data[] = $key;
        }
        sort($data);
        $this->sharedVariableCache->saveString('bus_messages', JsonUtil::encode($data), 60);

        // $this->sharedVariableCache->beginTransaction();
        $times = max($this->sharedVariableCache->readInt("bus_messages_{$key}") - 1, 0);
        $this->sharedVariableCache->saveInt("bus_messages_{$key}", $times, ttl: 60);
        // $this->sharedVariableCache->closeTransaction();
    }

    /**
     * @param Event|Query<Document>|Command $message
     */
    private function decreasePendingCounter(Event | Query | Command $message): void
    {
        $project = $this->contextService->project();
        $clzz = $message::class;
        $data = JsonUtil::decode($this->sharedVariableCache->readString('bus_messages_pending', '[]'));
        $key = "{$project}_{$clzz}";
        if (!in_array($key, $data, true)) {
            $data[] = $key;
        }
        sort($data);
        $this->sharedVariableCache->saveString('bus_messages_pending', JsonUtil::encode($data), 60);

        // $this->sharedVariableCache->beginTransaction();
        $times = max($this->sharedVariableCache->readInt("bus_messages_pending_{$key}") - 1, 0);
        $this->sharedVariableCache->saveInt("bus_messages_pending_{$key}", $times, ttl: 60);
        // $this->sharedVariableCache->closeTransaction();
    }

    /**
     * @param Event|Query<Document>|Command $message
     */
    private function increaseCounter(Event | Query | Command $message): void
    {
        $project = $this->contextService->project();
        $clzz = $message::class;
        $data = JsonUtil::decode($this->sharedVariableCache->readString('bus_messages', '[]'));
        $key = str_replace('\\', '_', "{$project}_{$clzz}");
        if (!in_array($key, $data, true)) {
            $data[] = $key;
        }
        sort($data);
        $this->sharedVariableCache->saveString('bus_messages', JsonUtil::encode($data), 60);

        // $this->sharedVariableCache->beginTransaction();
        $times = min(100_000, $this->sharedVariableCache->readInt("bus_messages_{$key}") + 1);
        $this->sharedVariableCache->saveInt("bus_messages_{$key}", $times, ttl: 60);
        // $this->sharedVariableCache->closeTransaction();
    }

    /**
     * @param Event|Query<Document>|Command $message
     */
    private function increasePendingCounter(Event | Query | Command $message): void
    {
        $project = $this->contextService->project();
        $clzz = $message::class;
        $key = "{$project}_{$clzz}";
        $data = JsonUtil::decode($this->sharedVariableCache->readString('bus_messages_pending', '[]'));
        if (!in_array($key, $data, true)) {
            $data[] = $key;
        }
        sort($data);
        $this->sharedVariableCache->saveString('bus_messages_pending', JsonUtil::encode($data), 60);

        // $this->sharedVariableCache->beginTransaction();
        $times = min(1_000_000, $this->sharedVariableCache->readInt("bus_messages_pending_{$key}") + 1);
        $this->sharedVariableCache->saveInt("bus_messages_pending_{$key}", $times, ttl: 60);
        // $this->sharedVariableCache->closeTransaction();
    }

    private function normalizeClass(string $class): string
    {
        return strtolower(str_replace('\\', '_', $class));
    }
}
