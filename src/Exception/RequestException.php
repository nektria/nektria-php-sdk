<?php

declare(strict_types=1);

namespace Nektria\Exception;

use Nektria\Dto\RequestResponse;

class RequestException extends NektriaRuntimeException
{
    public function __construct(
        private readonly RequestResponse $response,
        bool $silent = false
    ) {
        parent::__construct(
            errorCode: "RE_{$this->response->status}",
            message: "Request Failed: {$this->response->status} {$this->response->method} {$this->response->url}",
            silent: $silent
        );
    }

    public function response(): RequestResponse
    {
        return $this->response;
    }
}
