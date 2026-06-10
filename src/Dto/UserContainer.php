<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Document\User;

class UserContainer
{
    private ?User $user;

    public function __construct()
    {
        $this->user = null;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
        LocalClock::defaultTimezone($user?->tenant->timezone ?? 'UTC');
    }

    public function user(): ?User
    {
        return $this->user;
    }
}
