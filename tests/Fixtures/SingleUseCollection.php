<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use Generator;
use IteratorAggregate;
use Traversable;

/**
 * Backed by a generator, so it can be traversed only once. Reading it before
 * registering its identity turns a cycle into a closed-generator error.
 *
 * @implements IteratorAggregate<int|string, mixed>
 */
class SingleUseCollection implements IteratorAggregate
{
    private Generator $generator;

    public function __construct(callable $factory)
    {
        $this->generator = $factory();
    }

    public function map(callable $callback): static
    {
        return $this;
    }

    public function getIterator(): Traversable
    {
        return $this->generator;
    }
}
