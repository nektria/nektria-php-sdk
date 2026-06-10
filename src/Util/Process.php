<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Exception\NektriaException;

use function is_string;

use const PHP_EOL;

class Process
{
    /**
     * @param (callable('out'|'err', string):void)|null $callback
     * @param array<string, string> $envs
     * @param string|string[] $command
     */
    public static function command(
        string | array $command,
        ?array $envs = null,
        ?callable $callback = null
    ): string {
        if (is_string($command)) {
            return self::command(explode(' ', $command), envs: $envs, callback: $callback);
        }

        $process = new \Symfony\Component\Process\Process($command);
        $process->setTimeout(600);
        $process->setEnv($envs ?? []);
        $process->run($callback);

        if (!$process->isSuccessful()) {
            throw new NektriaException('E_500', $process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * @param (callable('out'|'err', string):void)|null $callback
     * @param array<string, string> $envs
     * @param string|string[] $command
     */
    public static function jsonCommand(
        string | array $command,
        ?array $envs = null,
        ?callable $callback = null
    ): mixed {
        return JsonUtil::decode(self::command($command, envs: $envs, callback: $callback));
    }

    /**
     * @param (callable('out'|'err', string):void)|null $callback
     * @param array<string, string> $envs
     * @param string|string[] $command
     * @return string[]
     */
    public static function linesCommand(
        string | array $command,
        ?array $envs = null,
        ?callable $callback = null
    ): array {
        return explode(PHP_EOL, self::command($command, envs: $envs, callback: $callback));
    }

    /**
     * @param (callable('out'|'err', string):void)|null $callback
     * @param array<string, string> $envs
     */
    public static function shellCommand(
        string $shell,
        ?array $envs = null,
        ?callable $callback = null
    ): string {
        $process = \Symfony\Component\Process\Process::fromShellCommandline($shell);
        $process->setTimeout(600);
        $process->setEnv($envs ?? []);
        $process->run($callback);

        if (!$process->isSuccessful()) {
            throw new NektriaException('E_500', $process->getErrorOutput());
        }

        return $process->getOutput();
    }
}
