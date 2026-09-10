<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\CastTo;

class ConflictingCastDTO extends ArgonautDTO
{
    #[CastTo(TagDTO::class)]
    public mixed $value = null;

    /** @var array<string, string> */
    protected array $casts = ['value' => 'string'];
}
