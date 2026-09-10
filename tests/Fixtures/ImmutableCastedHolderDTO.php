<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautImmutableDTO;

class ImmutableCastedHolderDTO extends ArgonautImmutableDTO
{
    public readonly TagDTO $tag;

    /** @var array<string, string> */
    protected array $casts = ['tag' => TagDTO::class];
}
