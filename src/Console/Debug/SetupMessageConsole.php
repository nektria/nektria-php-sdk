<?php

declare(strict_types=1);

namespace Nektria\Console\Debug;

use Nektria\Console\Console;
use Nektria\Exception\NektriaException;
use Nektria\Service\ContextService;
use Nektria\Util\FileUtil;
use Symfony\Component\Console\Input\InputOption;

class SetupMessageConsole extends Console
{
    public function __construct(
        private readonly ContextService $contextService
    ) {
        parent::__construct('debug:setup:message');
    }

    protected function configure(): void
    {
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_REQUIRED,
            'Query or Command.',
            '',
        );

        $this->addOption(
            'message',
            'm',
            InputOption::VALUE_REQUIRED,
            'Message name.',
            '',
        );

        $this->addOption(
            'resource',
            'r',
            InputOption::VALUE_REQUIRED,
            'Resource name.',
            '',
        );
    }

    protected function play(): void
    {
        $resource = (string) $this->input()->getOption('resource');
        if ($resource === '') {
            throw new NektriaException('E_500', 'Resource name is required.');
        }

        $type = (string) $this->input()->getOption('type');
        if ($type === '') {
            throw new NektriaException('E_500', 'Message type is required.');
        }

        $message = (string) $this->input()->getOption('message');
        if ($message === '') {
            throw new NektriaException('E_500', 'Message is required.');
        }

        if (!is_dir("./src/Message/{$resource}")) {
            mkdir("./src/Message/{$resource}");
        }

        if (!is_dir("./src/MessageHandler/{$resource}")) {
            mkdir("./src/MessageHandler/{$resource}");
        }

        if ($type === 'Query') {
            $fromPath = 'vendor/nektria/php-sdk/assets/message/Query';
            $fromPathHandler = 'vendor/nektria/php-sdk/assets/message/QueryHandler';
        } elseif ($type === 'Command') {
            $fromPath = 'vendor/nektria/php-sdk/assets/message/Command';
            $fromPathHandler = 'vendor/nektria/php-sdk/assets/message/CommandHandler';
        } elseif ($type === 'Event') {
            $fromPath = 'vendor/nektria/php-sdk/assets/message/Event';
            $fromPathHandler = 'vendor/nektria/php-sdk/assets/message/EventHandler';
        } else {
            throw new NektriaException('E_500', 'Message type is invalid.');
        }

        $this->copyDir($fromPath, "./src/Message/{$resource}");
        $this->copyDir($fromPathHandler, "./src/MessageHandler/{$resource}");
    }

    private function copyDir(string $from, string $to): void
    {
        $files = scandir($from);

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $toFile = $this->fix($file);

            if ($toFile === '.git-ignore') {
                $toFile = '.gitignore';
            }

            $fromPath = "{$from}/{$file}";
            $toPath = "{$to}/{$toFile}";

            if (is_dir($fromPath)) {
                if (!is_dir($toPath) && !mkdir($toPath)) {
                    throw new NektriaException('E_500', "Directory '{$toPath}' was not created.");
                }
                $this->copyDir($fromPath, $toPath);
            } else {
                $this->copyFile($fromPath, $toPath);
            }
        }
    }

    private function copyFile(string $from, string $to): void
    {
        FileUtil::write($to, $this->fix(FileUtil::read($from)));
    }

    private function fix(string $text): string
    {
        $resource = (string) $this->input()->getOption('resource');
        $message = (string) $this->input()->getOption('message');

        $camelCaseResource = lcfirst($resource);
        $snakeCaseResource = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $resource));
        $hypenCaseResource = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $resource));
        $pathResource = $hypenCaseResource;
        if (!str_ends_with($pathResource, 's')) {
            $pathResource .= 's';
        }

        return str_replace(
            [
                '__PROJECT__',
                '__ENTITY__',
                '__ENTITY_CC__',
                '__ENTITY_SC__',
                '__ENTITY_HC__',
                '__ENTITY_PATH__',
                '__MESSAGE__',
            ],
            [
                $this->contextService->project(),
                $resource,
                $camelCaseResource,
                $snakeCaseResource,
                $hypenCaseResource,
                $pathResource,
                $message,
            ],
            $text,
        );
    }
}
