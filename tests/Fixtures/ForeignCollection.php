<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * A collection implementation the package knows nothing about, standing in for
 * Illuminate\Support\Collection. It implements only what the casting engine
 * actually needs: a constructor taking an array, map(), all(), and Traversable.
 *
 * @implements IteratorAggregate<int|string, mixed>
 */
class ForeignCollection implements Countable, IteratorAggregate
{
    /** @param array<int|string, mixed> $items */
    public function __construct(private array $items = []) {}

    /** @return array<int|string, mixed> */
    public function all(): array
    {
        return $this->items;
    }

    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items));
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
