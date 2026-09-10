<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use Generator;
use IteratorAggregate;
use Traversable;

/**
 * Generator-backed like SingleUseCollection, but with a real map(): mapping
 * produces a new collection over the transformed items rather than returning
 * the same instance.
 *
 * The cycle fixture's map() returns $this, which is enough to be recognised
 * but never exercises mapping. This one does, so the `collection:<DTO>` cast
 * is proven against a collection that can only be traversed once.
 *
 * The constructor takes an iterable, as the documented contract requires, and
 * wraps it in a generator — so an instance built by newCollection() from a
 * plain array is still single-use.
 *
 * @implements IteratorAggregate<int|string, mixed>
 */
class MappingSingleUseCollection implements IteratorAggregate
{
    private Generator $generator;

    /** @param iterable<int|string, mixed> $items */
    public function __construct(iterable $items = [])
    {
        $this->generator = (static function () use ($items): Generator {
            yield from $items;
        })();
    }

    public function map(callable $callback): static
    {
        // Keys are preserved: a cast over a keyed collection must not silently
        // reindex it.
        $mapped = [];

        foreach ($this->generator as $key => $item) {
            $mapped[$key] = $callback($item);
        }

        return new static($mapped);
    }

    public function getIterator(): Traversable
    {
        return $this->generator;
    }
}
