<?php

declare(strict_types=1);

namespace Nektria\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nektria\Dto\Clock;

abstract class EventEntity implements EntityInterface
{
    #[ORM\Column(type: 'guid')]
    protected string $tenantId;

    #[ORM\Id]
    #[ORM\Column(type: 'micro_clock', options: ['precision' => 6])]
    protected Clock $timestamp;

    public function __construct(string $tenantId)
    {
        $this->tenantId = $tenantId;
        $this->timestamp = Clock::now();
    }

    public function fixTimeStamp(): void
    {
        usleep(2);
        $this->timestamp = Clock::now();
    }

    public function id(): string
    {
        return (string) $this->timestamp;
    }

    public function refresh(): void
    {
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function timestamp(): Clock
    {
        return $this->timestamp;
    }
}
