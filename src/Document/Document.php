<?php

declare(strict_types=1);

namespace Nektria\Document;

use Nektria\Dto\Map;
use Nektria\Exception\NektriaException;
use Nektria\Service\ContextService;

readonly abstract class Document
{
    /**
     * @var Map<mixed[]>
     */
    private Map $cache;

    public function __construct()
    {
        $this->cache = new Map();
    }

    public function __toString(): string
    {
        if (property_exists($this, 'id')) {
            return (string) $this->id;
        }

        $clss = $this::class;

        throw new NektriaException('E_500', "Document {$clss} does not have an id attribute");
    }

    /**
     * @return mixed[]
     */
    final public function data(?ContextService $context): array
    {
        // @phpstan-ignore-next-line
        if (!isset($this->cache)) {
            return $this->toArray($context);
        }

        $ctx = $context?->context() ?? 'null';

        if ($this->cache->has($ctx)) {
            return $this->cache->get($ctx);
        }

        $data = $this->toArray($context);
        $this->cache->set($ctx, $data);

        return $data;
    }

    /**
     * @return mixed[]
     */
    abstract protected function toArray(?ContextService $context): array;
}
