<?php

declare(strict_types=1);

namespace Nektria\Console\Debug;

use Nektria\Console\Console;
use Nektria\Exception\NektriaException;
use Nektria\Service\ContextService;
use Nektria\Util\FileUtil;
use Symfony\Component\Console\Input\InputOption;

class SetupResourceConsole extends Console
{
    public function __construct(
        private readonly ContextService $contextService
    ) {
        parent::__construct('debug:setup:resource');
    }

    protected function configure(): void
    {
        $this->addOption(
            'override',
            'o',
            InputOption::VALUE_NONE,
            'Override existing files.',
        );
        $this->addOption(
            'resource',
            'r',
            InputOption::VALUE_REQUIRED,
            'Resource name.',
            ''
        );
    }

    protected function play(): void
    {
        $this->copyDir('vendor/nektria/php-sdk/assets/entity', '.');
        $this->output()->writeln('done');
    }

    private function copyDir(string $from, string $to): void
    {
        $override = (bool) $this->input()->getOption('override');

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
                if (!$override && is_file($toPath)) {
                    continue;
                }
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
        $camelCaseResource = lcfirst($resource);
        $snakeCaseResource = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $resource));
        $hypenCaseResource = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $resource));
        $pathResource = $hypenCaseResource;
        if (!str_ends_with($pathResource, 's')) {
            $pathResource .= 's';
        }

        if ($resource === '') {
            throw new NektriaException('E_500', 'Resource name is required.');
        }

        return str_replace(
            [
                '__PROJECT__',
                '__ENTITY__',
                '__ENTITY_CC__',
                '__ENTITY_SC__',
                '__ENTITY_HC__',
                '__ENTITY_PATH__',
            ],
            [
                $this->contextService->project(),
                $resource,
                $camelCaseResource,
                $snakeCaseResource,
                $hypenCaseResource,
                $pathResource,
            ],
            $text
        );
    }
}
