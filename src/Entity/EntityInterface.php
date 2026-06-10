<?php

declare(strict_types=1);

namespace Nektria\Entity;

interface EntityInterface
{
    public function id(): string;

    public function refresh(): void;
}
