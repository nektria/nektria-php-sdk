<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Util\JsonUtil;
use Throwable;

readonly class RequestResponse
{
    /**
     * @param array<string, string|int|bool> $requestHeaders
     * @param array<string, (string|int|bool)[]> $responseHeaders
     * @param array<string, string> $cookies
     */
    public function __construct(
        public string $method,
        public string $url,
        public int $status,
        public string $body,
        public array $requestHeaders,
        public array $responseHeaders,
        public array $cookies,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->status < 400;
    }

    public function json(): mixed
    {
        try {
            return JsonUtil::decode($this->body);
        } catch (Throwable) {
            return [
                '_response' => $this->body,
            ];
        }
    }
}
