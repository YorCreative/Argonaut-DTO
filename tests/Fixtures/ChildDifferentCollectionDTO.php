<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\Collection;

/**
 * Nested inside a parent that uses a different collection class, so each level
 * must build and serialize with its own.
 */
class ChildDifferentCollectionDTO extends ForeignCollectionCastDTO
{
    protected function collectionClass(): string
    {
        return Collection::class;
    }
}
