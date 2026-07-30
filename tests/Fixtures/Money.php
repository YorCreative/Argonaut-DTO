<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/**
 * A plain value object (not an Argonaut DTO) whose constructor takes a scalar.
 * Casting to classes like this must keep working.
 */
class Money
{
    public function __construct(public readonly string $amount) {}
}
