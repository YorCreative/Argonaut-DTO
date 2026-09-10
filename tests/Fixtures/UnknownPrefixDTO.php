<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Names something that is neither this package's Collection nor the class
 * collectionClass() returns, so it must not be treated as a collection cast.
 */
class UnknownPrefixDTO extends ArgonautDTO
{
    protected array $casts = ['tags' => 'SomeOther\\Collection:'.TagDTO::class];

    public mixed $tags = null;
}
