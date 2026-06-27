<?php

declare(strict_types=1);

namespace Nektria\Document;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Nektria\Exception\NektriaException;
use Nektria\Service\ContextService;
use Nektria\Util\ArrayUtil;
use Traversable;

use function array_slice;
use function count;

/**
 * @implements ArrayAccess<int, T>
 * @implements IteratorAggregate<int, T>
 * @template T of Document
 */
readonly class NewDocumentCollection extends Document implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * @param T[] $items
     */
    public function __construct(
        private array $items = []
    ) {
        parent::__construct();
    }

    /**
     * @template X of Document
     * @param DocumentCollection<X> $old
     * @return NewDocumentCollection<X>
     */
    public static function fromOldDocumentCollection(DocumentCollection $old): self
    {
        return new self($old->list());
    }

    /**
     * @template X of Document
     * @param NewDocumentCollection<X> $a
     * @param NewDocumentCollection<X> $b
     * @return NewDocumentCollection<X>
     */
    public static function merge(self $a, self $b): self
    {
        return new self([...$a->items, ...$b->items]);
    }

    /**
     * @param T[] $items
     * @return NewDocumentCollection<T>
     */
    public static function new(array $items): self
    {
        return new self($items);
    }

    /**
     * @return array<string, NewDocumentCollection<T>>
     */
    public function classify(string $field): array
    {
        $tmp = ArrayUtil::classify(
            $this->items,
            static fn (Document $item) => $item->data(null)[$field] ?? 'null'
        );

        $ret = [];

        foreach ($tmp as $key => $value) {
            $ret[$key] = new self($value);
        }

        return $ret;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @param callable(T): bool $callback
     * @return NewDocumentCollection<T>
     */
    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->items, $callback)));
    }

    /**
     * @return T|null
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return T
     */
    public function get(int $key): Document
    {
        return $this->items[$key];
    }

    public function getIterator(): Traversable
    {
        foreach ($this->items as $key => $val) {
            yield $key => $val;
        }
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return T|null
     */
    public function last()
    {
        return $this->items[$this->count() - 1] ?? null;
    }

    /**
     * @return T[]
     */
    public function list(): array
    {
        return $this->items;
    }

    /**
     * @return array<string, T>
     */
    public function mapify(string $field): array
    {
        return ArrayUtil::mapify(
            $this->items,
            static fn (Document $item) => $item->data(null)[$field] ?? 'null'
        );
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @return T
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new NektriaException('E_500', 'DocumentCollection is read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new NektriaException('E_500', 'DocumentCollection is read-only');
    }

    /**
     * @return T|null
     */
    public function opt(int $key): ?Document
    {
        return $this->items[$key];
    }

    /**
     * @return NewDocumentCollection<T>
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->items));
    }

    /**
     * @return NewDocumentCollection<T>
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new self(array_slice($this->items, $offset, $length));
    }

    /**
     * @param callable(T, T): int $callback
     * @return NewDocumentCollection<T>
     */
    public function sort(callable $callback): self
    {
        $data = [...$this->items];
        usort($data, $callback);

        return new self($data);
    }

    /**
     * @return mixed[]
     */
    protected function toArray(?ContextService $context): array
    {
        $list = [];

        foreach ($this as $item) {
            $list[] = $item->toArray($context);
        }

        return [
            'list' => $list,
        ];
    }
}
