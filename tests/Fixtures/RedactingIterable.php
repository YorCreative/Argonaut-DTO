<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Iterable and JsonSerializable, where the two disagree on purpose: the
 * iterator exposes the raw value, jsonSerialize() the redacted one.
 *
 * @implements IteratorAggregate<string, string>
 */
class RedactingIterable implements IteratorAggregate, JsonSerializable
{
    public function getIterator(): Traversable
    {
        return new ArrayIterator(['token' => 'secret']);
    }

    public function jsonSerialize(): mixed
    {
        return ['token' => '[REDACTED]'];
    }
}
