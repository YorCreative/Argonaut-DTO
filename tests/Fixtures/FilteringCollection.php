<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use ArrayIterator;
use Traversable;
use YorCreative\ArgonautDTO\Collection;

/**
 * A subclass whose getIterator() publishes less than it stores. Serialization
 * must emit what iteration yields, not what all() returns.
 *
 * @extends Collection<mixed>
 */
class FilteringCollection extends Collection
{
    public function getIterator(): Traversable
    {
        return new ArrayIterator(array_diff_key($this->all(), ['secret' => null]));
    }
}
