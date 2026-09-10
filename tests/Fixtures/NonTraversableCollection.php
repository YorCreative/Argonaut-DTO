<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/** Mappable, but cannot be walked back into an array. */
class NonTraversableCollection
{
    /** @param array<int|string, mixed> $items */
    public function __construct(public array $items = []) {}

    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items));
    }
}
