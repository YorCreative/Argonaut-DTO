<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class ForeignCollectionCastDTO extends ArgonautDTO
{
    protected array $casts = ['tags' => 'collection:'.TagDTO::class];

    public mixed $tags = null;

    protected function collectionClass(): string
    {
        return ForeignCollection::class;
    }
}
