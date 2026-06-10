<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Service\ContextService;

readonly class ArrayDocument extends Document
{
    public function __construct(public mixed $data)
    {
        parent::__construct();
    }

    protected function toArray(?ContextService $context): array
    {
        return $this->data;
    }
}
