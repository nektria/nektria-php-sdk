<?php

declare(strict_types=1);

namespace Nektria\Console\Debug;

use Nektria\Console\Console;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;

class IncreaseVersionConsole extends Console
{
    public function __construct()
    {
        parent::__construct('debug:increase-version');
    }

    protected function play(): void
    {
        $composer = JsonUtil::decode(FileUtil::read('composer.json'));
        [$desc, $version] = explode(' - ', $composer['description']);
        [$mayor, $minor1, $commit] = explode('.', $version);
        $commit = (string) ((int) $commit + 1);
        $newVersion = "$mayor.$minor1.$commit";
        $composer['description'] = "{$desc} - {$newVersion}";
        FileUtil::write(
            'composer.json',
            str_replace('\/', '/', JsonUtil::encode($composer, true)),
        );

        $this->output()->write($newVersion);
    }
}
