<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class ParentWithDifferentChildDTO extends ArgonautDTO
{
    protected array $casts = [
        'children' => 'collection:'.ChildDifferentCollectionDTO::class,
    ];

    public mixed $children = null;

    protected function collectionClass(): string
    {
        return ForeignCollection::class;
    }
}
