<?php

declare(strict_types=1);

namespace Nektria\Message;

interface Event
{
    public function ref(): string;
}
