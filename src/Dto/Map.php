<?php

declare(strict_types=1);

namespace Nektria\Dto;

/**
 * @template V
 */
class Map
{
    /**
     * @var array<string, V>
     */
    private array $map = [];

    public function __construct()
    {
    }

    public function get(string $key): mixed
    {
        return $this->map[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->map[$key]);
    }

    public function set(string $key, mixed $value): void
    {
        $this->map[$key] = $value;
    }
}
