<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/** Neither mappable nor traversable. */
class UnusableCollection
{
    /** @param array<int|string, mixed> $items */
    public function __construct(public array $items = []) {}
}
