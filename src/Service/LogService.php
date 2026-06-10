<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\ThrowableDocument;
use Nektria\Infrastructure\SharedLogCache;
use Nektria\Util\JsonUtil;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\RejectRedeliveredMessageException;
use Throwable;

use function in_array;

use const PHP_EOL;

readonly class LogService extends AbstractService
{
    public const string DEBUG = 'DEBUG';

    public const string EMERGENCY = 'EMERGENCY';

    public const string ERROR = 'ERROR';

    public const string INFO = 'INFO';

    public const string WARNING = 'WARNING';

    /** @var mixed[] */
    private array $data;

    public function __construct(
        private ContextService $contextService,
        private SharedLogCache $sharedLogCache,
    ) {
        parent::__construct();

        if ($this->contextService->isLocalEnvironment()) {
            $this->data = ['channel' => false];
        } else {
            $this->data = ['channel' => fopen('php://stderr', 'wb')];
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function debug(
        array $payload,
        string $message,
        bool $ignoreRedis = false
    ): void {
        $user = $this->securityService()->currentUser();
        if (!$ignoreRedis && ($this->data['channel'] === false || !$this->contextService->debugMode())) {
            $this->sharedLogCache->addLog([
                'labels' => $this->registry()->getMetadata()->data(),
                'message' => $message,
                'payload' => $payload,
                'project' => $this->contextService->project(),
                'tenant' => $user->tenant->alias ?? 'none',
                'tenantId' => $user->tenant->id ?? 'none',
                'authId' => $user->id ?? 'none',
            ]);

            return;
        }

        if ($this->data['channel'] === false) {
            return;
        }

        try {
            $data = $this->build($payload, $message, self::DEBUG);
            $body = JsonUtil::encode($data);
            fwrite($this->data['channel'], $body . PHP_EOL);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function error(array $payload, string $message): void
    {
        if ($this->data['channel'] === false) {
            return;
        }

        try {
            $data = $this->build($payload, $message, self::ERROR);
            $body = JsonUtil::encode($data);
            fwrite($this->data['channel'], $body . PHP_EOL);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * @param mixed[] $extra
     */
    public function exception(Throwable $exception, array $extra = [], bool $asWarning = false): void
    {
        if ($this->data['channel'] === false) {
            return;
        }

        $ignoreList = [
            RejectRedeliveredMessageException::class,
            RecoverableMessageHandlingException::class,
        ];

        if (in_array($exception::class, $ignoreList, true)) {
            return;
        }

        $tmp = new ThrowableDocument($exception);
        $clearTrace = $tmp->trace();
        $user = $this->securityService()->currentUser();

        try {
            $data = [
                'message' => $exception->getMessage(),
                'logName' => 'projects/nektria/logs/error',
                'severity' => $asWarning ? self::WARNING : self::EMERGENCY,
                'logging.googleapis.com/labels' => [
                    ...$this->registry()->getMetadata()->data(),
                    ...[
                        'app' => $this->contextService->project(),
                        'env' => $this->contextService->env(),
                        'tenant' => $user->tenant->alias ?? 'none',
                        'tenantId' => $user->tenant->id ?? 'none',
                        'authId' => $user->id ?? 'none',
                    ]
                ],
                'logging.googleapis.com/trace_sampled' => false,
            ];

            $data['logging.googleapis.com/trace'] = $this->contextService->traceId();

            $payload = [
                'type' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $clearTrace,
                'extra' => $extra,
            ];

            $data = array_merge($payload, $data);

            try {
                $body = JsonUtil::encode($data);
                fwrite($this->data['channel'], $body . PHP_EOL);
            } catch (Throwable) {
                return;
            }
        } catch (Throwable) {
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function info(array $payload, string $message): void
    {
        if ($this->data['channel'] === false) {
            return;
        }

        try {
            $data = $this->build($payload, $message, self::INFO);
            $body = JsonUtil::encode($data);
            fwrite($this->data['channel'], $body . PHP_EOL);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function send(
        string $level,
        array $payload,
        string $message,
    ): void {
        match ($level) {
            self::INFO => $this->info($payload, $message),
            self::WARNING => $this->warning($payload, $message),
            self::DEBUG => $this->debug($payload, $message),
            self::ERROR => $this->error($payload, $message),
            default => false,
        };
    }

    public function temporalLogs(): void
    {
        if ($this->data['channel'] === false) {
            return;
        }

        $logs = $this->sharedLogCache->getLogs();

        foreach ($logs as $log) {
            $data = [
                'message' => $log['message'],
                'logName' => "projects/nektria/logs/{$log['project']}",
                'severity' => self::DEBUG,
                'logging.googleapis.com/labels' => [...($log['labels'] ?? []), ...[
                    'app' => $log['project'],
                    'env' => $this->contextService->env(),
                    'tenant' => $log['tenant'] ?? 'none',
                    'tenantId' => $log['tenantId'] ?? 'none',
                    'authId' => $log['authId'] ?? 'none',
                ]],
                'logging.googleapis.com/trace' => $this->contextService->traceId(),
                'logging.googleapis.com/trace_sampled' => false,
            ];

            $data = array_merge($log['payload'], $data);

            try {
                $body = JsonUtil::encode($data);
                fwrite($this->data['channel'], $body . PHP_EOL);
            } catch (Throwable) {
                return;
            }
        }
    }

    /**
     * @param mixed[] $payload
     */
    public function warning(array $payload, string $message): void
    {
        try {
            if ($this->data['channel'] === false) {
                return;
            }
            $data = $this->build($payload, $message, self::WARNING);
            fwrite($this->data['channel'], JsonUtil::encode($data) . PHP_EOL);
        } catch (Throwable) {
        }
    }

    /**
     * @param mixed[] $payload
     * @return mixed[]
     */
    private function build(
        array $payload,
        string $message,
        string $level
    ): array {
        $user = $this->securityService()->currentUser();

        $data = [
            'message' => $message,
            'logName' => "projects/nektria/logs/{$this->contextService->project()}",
            'severity' => $level,
            'logging.googleapis.com/labels' => [...$this->registry()->getMetadata()->data(), ...[
                'app' => $this->contextService->project(),
                'env' => $this->contextService->env(),
                'tenant' => $user->tenant->alias ?? 'none',
                'tenantId' => $user->tenant->id ?? 'none',
                'authId' => $user->id ?? 'none',
            ]],
            'logging.googleapis.com/trace' => $this->contextService->traceId(),
            'logging.googleapis.com/trace_sampled' => false,
        ];

        return array_merge($payload, $data);
    }
}
