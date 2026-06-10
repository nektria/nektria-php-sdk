<?php

declare(strict_types=1);

namespace Nektria\Util\MessageStamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

readonly class ContextStamp implements StampInterface
{
    public function __construct(
        public string $traceId,
        public string $context,
        public ?string $tenantId,
        public ?string $userId
    ) {
    }
}
