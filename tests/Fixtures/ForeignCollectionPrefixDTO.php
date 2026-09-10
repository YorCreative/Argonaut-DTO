<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Writes the cast directive using its OWN collection class as the prefix,
 * the way a consumer naturally would.
 */
class ForeignCollectionPrefixDTO extends ArgonautDTO
{
    protected array $casts = ['tags' => ForeignCollection::class.':'.TagDTO::class];

    public mixed $tags = null;

    protected function collectionClass(): string
    {
        return ForeignCollection::class;
    }
}
