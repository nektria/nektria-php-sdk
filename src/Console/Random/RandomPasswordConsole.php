<?php

declare(strict_types=1);

namespace Nektria\Console\Random;

use Nektria\Console\Console;
use Nektria\Util\StringUtil;
use Symfony\Component\Console\Input\InputOption;

class RandomPasswordConsole extends Console
{
    public function __construct()
    {
        parent::__construct('debug:random:password');
    }

    protected function configure(): void
    {
        $this->addArgument('length', description: 'Length of the password');
        $this->addOption('full', shortcut: 'f', mode: InputOption::VALUE_NONE, description: 'with symbols');
    }

    protected function play(): void
    {
        $length = max(8, (int) $this->input()->getArgument('length'));
        $full = $this->input()->getOption('full') === true;

        $password = StringUtil::bytes(
            $length,
            symbols: $full,
        );

        if ($this->copy($password)) {
            $this->output()->writeln('<info>Copied to clipboard</info>');
        }

        $this->output()->writeln($password);
    }
}
