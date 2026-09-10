<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Replaces the factory itself, so validation living inside newCollection()
 * would never run.
 */
class OverriddenFactoryDTO extends ArgonautDTO
{
    protected array $casts = ['tags' => 'collection:'.TagDTO::class];

    public mixed $tags = null;

    protected function collectionClass(): string
    {
        return NonTraversableCollection::class;
    }

    protected function newCollection(array $items): mixed
    {
        return new NonTraversableCollection($items);
    }
}
