<?php

declare(strict_types=1);

namespace Nektria\Dto;

/**
 * @template T
 */
class ArrayContainer
{
    /** @var T[] */
    private array $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function empty(): void
    {
        $this->data = [];
    }

    /**
     * @return T[]
     */
    public function list(): array
    {
        return $this->data;
    }

    /**
     * @param T $element
     */
    public function push($element): void
    {
        $this->data[] = $element;
    }
}
