<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Holds a collection of DTOs, each of which holds a custom-cast collection —
 * so both levels build and serialize through a configured collection class.
 */
class NestedCustomCastHolderDTO extends ArgonautDTO
{
    protected array $casts = ['inner' => 'collection:'.ForeignCollectionCustomCastDTO::class];

    public mixed $inner = null;

    protected function collectionClass(): string
    {
        return ForeignCollection::class;
    }
}
