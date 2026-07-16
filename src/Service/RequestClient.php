<?php

declare(strict_types=1);

namespace Nektria\Service;

use Override;

readonly class RequestClient extends BaseRequestClient
{
    #[Override]
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Nektria/1.0',
            'X-Origin' => $this->contextService()->project(),
        ];
    }
}
