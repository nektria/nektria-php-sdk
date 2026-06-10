<?php

declare(strict_types=1);

namespace Nektria\Service;

readonly class RequestClient extends BaseRequestClient
{
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Nektria/1.0',
            'X-Origin' => $this->contextService()->project(),
        ];
    }
}
