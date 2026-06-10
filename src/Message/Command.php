<?php

declare(strict_types=1);

namespace Nektria\Message;

interface Command
{
    public function ref(): string;
}
