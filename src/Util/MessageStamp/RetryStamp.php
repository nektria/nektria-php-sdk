<?php

declare(strict_types=1);

namespace Nektria\Util\MessageStamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class RetryStamp implements StampInterface
{
    public function __construct(
        public readonly int $currentTry,
        public readonly int $maxRetries,
        public readonly int $intervalMs
    ) {
    }
}
