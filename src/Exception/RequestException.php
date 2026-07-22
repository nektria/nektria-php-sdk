<?php

declare(strict_types=1);

namespace Nektria\Exception;

use Nektria\Dto\RequestResponse;

class RequestException extends NektriaException
{
    public function __construct(
        private readonly RequestResponse $response,
        bool $silent = false,
    ) {
        parent::__construct(
            message: "Request Failed: {$this->response->status} {$this->response->method} {$this->response->url}",
            status: $this->response->status,
            options: [
                'convertToAlert' => $silent,
            ],
        );
    }

    public function response(): RequestResponse
    {
        return $this->response;
    }
}
