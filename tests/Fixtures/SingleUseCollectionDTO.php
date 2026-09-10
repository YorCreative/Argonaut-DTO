<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class SingleUseCollectionDTO extends ArgonautDTO
{
    public mixed $payload = null;

    protected function collectionClass(): string
    {
        return SingleUseCollection::class;
    }
}
