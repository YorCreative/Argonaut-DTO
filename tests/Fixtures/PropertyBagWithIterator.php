<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * An ordinary object that happens to be iterable, whose iterator yields
 * something unrelated to its properties. A single-model cast must read the
 * properties, not the iterator.
 *
 * @implements IteratorAggregate<string, string>
 */
class PropertyBagWithIterator implements IteratorAggregate
{
    public string $name = 'property-name';

    public function getIterator(): Traversable
    {
        return new ArrayIterator(['unrelated' => 'iterator-entry']);
    }
}
