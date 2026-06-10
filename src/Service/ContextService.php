<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedVariableCache;
use Nektria\Util\StringUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContextService
{
    public const string ADMIN = 'admin';

    public const string COMMON = 'common';

    public const string DEV = 'dev';

    public const string INTERNAL = 'internal';

    public const string PRIVATE = 'private';

    public const string PROD = 'prod';

    public const string PUBLIC = 'public';

    public const string PUBLIC_V2 = 'public_v2';

    public const string QA = 'qa';

    public const string STAGING = 'staging';

    public const string SYSTEM = 'system';

    public const string TEST = 'test';

    public static ?ContextService $dummyCS;

    private string $context;

    /**
     * @var array<string, string>
     */
    private array $extras;

    private bool $forceSync;

    private string $traceId;

    public function __construct(
        private readonly SharedVariableCache $sharedVariableCache,
        private readonly string $env,
        private readonly string $project,
    ) {
        $this->context = self::COMMON;
        $this->traceId = StringUtil::uuid4();
        $this->forceSync = false;
        $this->extras = [];
    }

    public static function internal(): self
    {
        self::$dummyCS ??= new self(new SharedVariableCache('', ''), self::INTERNAL, 'dummy');
        self::$dummyCS->setContext(self::INTERNAL);

        return self::$dummyCS;
    }

    public function addExtra(string $key, ?string $value): void
    {
        if ($value === null) {
            unset($this->extras[$key]);
        } else {
            $this->extras[$key] = $value;
        }
    }

    public function context(): string
    {
        return $this->context;
    }

    public function debugMode(?string $project = null, bool $ignoreLocalEnvironment = false): bool
    {
        $project ??= $this->project;

        if ($ignoreLocalEnvironment) {
            return
                $this->traceId === '00000000-0000-4000-8000-000000000000'
                || $this->sharedVariableCache->hasKey("debug_bbf6c8f_{$project}");
        }

        return
            $this->isLocalEnvironment()
            || $this->traceId === '00000000-0000-4000-8000-000000000000'
            || $this->sharedVariableCache->hasKey("debug_bbf6c8f_{$project}");
    }

    /**
     * @param string[] $projects
     * @return array<string, bool>
     */
    public function debugModes(array $projects): array
    {
        $data = [];
        foreach ($projects as $project) {
            $data[$project] = $this->debugMode(
                project: $project,
                ignoreLocalEnvironment: true
            );
        }

        return $data;
    }

    public function env(): string
    {
        return $this->env;
    }

    public function forceSync(): bool
    {
        return $this->forceSync;
    }

    public function getExtra(string $key): ?string
    {
        return $this->extras[$key] ?? null;
    }

    public function isDev(): bool
    {
        return $this->env === self::DEV;
    }

    public function isLocalEnvironment(): bool
    {
        return $this->env === self::DEV;
    }

    public function isPlayEnvironment(): bool
    {
        return $this->isDev() || $this->isTest() || $this->isQA();
    }

    public function isPrivate(): bool
    {
        return $this->context === self::PRIVATE;
    }

    public function isProd(): bool
    {
        return $this->env === self::PROD;
    }

    public function isPublic(): bool
    {
        return $this->context === self::PUBLIC || $this->context === self::PUBLIC_V2;
    }

    public function isQA(): bool
    {
        return $this->env === self::QA;
    }

    public function isRealEnvironment(): bool
    {
        return $this->isStaging() || $this->isProd();
    }

    public function isStaging(): bool
    {
        return $this->env === self::STAGING;
    }

    public function isTest(): bool
    {
        return $this->env === self::TEST;
    }

    public function project(): string
    {
        return $this->project;
    }

    public function setContainer(ContainerInterface $container): void
    {
    }

    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    /**
     * @param string[] $projects
     */
    public function setDebugMode(bool $enable, array $projects, int $ttl): void
    {
        foreach ($projects as $project) {
            $key = "debug_bbf6c8f_{$project}";
            if ($enable) {
                $this->sharedVariableCache->saveKey($key, $ttl);
            } else {
                $this->sharedVariableCache->deleteKey($key);
            }
        }
    }

    /**
     * @param string[] $projects
     */
    public function setDelaysRabbit(bool $enable, array $projects, int $ttl): void
    {
        foreach ($projects as $project) {
            $key = "delay_rabbit_85b20ef3_{$project}";
            if ($enable) {
                $this->sharedVariableCache->deleteKey($key);
            } else {
                $this->sharedVariableCache->saveKey($key, $ttl);
            }
        }
    }

    public function setForceSync(bool $forceSync): void
    {
        $this->forceSync = $forceSync;
    }

    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function traceId(): string
    {
        return $this->traceId;
    }
}
