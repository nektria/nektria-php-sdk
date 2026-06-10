<?php

declare(strict_types=1);

namespace Nektria\Console\Random;

use Nektria\Console\Console;
use Nektria\Util\StringUtil;

class RandomUuidConsole extends Console
{
    public function __construct()
    {
        parent::__construct('debug:random:uuid');
    }

    protected function play(): void
    {
        $id = StringUtil::uuid4();

        if ($this->copy($id)) {
            $this->output()->writeln('<info>Copied to clipboard</info>');
        }
        $this->output()->writeln($id);
    }
}
