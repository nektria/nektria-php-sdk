<?php

declare(strict_types=1);

namespace Nektria\Dto;

class SocketInfo
{
    private ?string $socketsToken;

    /**
     * @var string[]|null
     */
    private ?array $topics;

    public function __construct()
    {
        $this->socketsToken = null;
        $this->topics = null;
    }

    /**
     * @param string[] $allowedTopics
     */
    public function appendSockets(string $token, array $allowedTopics): void
    {
        $this->topics = $allowedTopics;
        $this->socketsToken = $token;
    }

    public function socketsToken(): ?string
    {
        return $this->socketsToken;
    }

    /**
     * @return string[]|null
     */
    public function topics(): ?array
    {
        return $this->topics;
    }
}
