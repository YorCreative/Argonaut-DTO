<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Left on the default collection class, so it also pins that the default is
 * unchanged. `single` casts to one model and is fed a foreign collection.
 */
class ForeignCollectionHolderDTO extends ArgonautDTO
{
    protected array $casts = [
        'tags' => 'collection:'.TagDTO::class,
        'single' => TagDTO::class,
    ];

    public mixed $tags = null;

    public ?TagDTO $single = null;
}
