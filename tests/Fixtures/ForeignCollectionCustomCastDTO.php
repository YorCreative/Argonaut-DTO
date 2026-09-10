<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class ForeignCollectionCustomCastDTO extends ArgonautDTO
{
    protected array $casts = ['tags' => 'collection:'.UppercaseTagCast::class];

    public mixed $tags = null;

    protected function collectionClass(): string
    {
        return ForeignCollection::class;
    }
}
