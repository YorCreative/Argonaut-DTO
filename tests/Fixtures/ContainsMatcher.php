<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/**
 * A non-Closure callable in the [$object, 'method'] form, accepting the
 * ($item, $key) signature Collection predicates are invoked with.
 */
class ContainsMatcher
{
    public function __construct(private readonly mixed $needle) {}

    public function matches(mixed $item, int|string $key): bool
    {
        return $item === $this->needle;
    }
}
