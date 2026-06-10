<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Exception\NektriaException;

/**
 * @template T
 */
class MapSet
{
    /**
     * @var array<string, T>
     */
    public private(set) array $content;

    public function __construct()
    {
        $this->content = [];
    }

    public function get(string $key): mixed
    {
        return $this->content[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->content[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->content[$key]);
    }

    public function retrieve(string $key): mixed
    {
        $value = $this->get($key);
        if ($value === null) {
            throw new NektriaException('E_500', "Key '$key' not found in set");
        }

        return $value;
    }

    /**
     * @param T $content
     */
    public function set(string $key, mixed $content): void
    {
        $this->content[$key] = $content;
    }
}
