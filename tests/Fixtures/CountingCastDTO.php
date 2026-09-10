<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class CountingCastDTO extends ArgonautDTO
{
    public mixed $a = null;

    public mixed $b = null;

    /** @var array<string, string> */
    protected array $casts = ['a' => CountingCast::class, 'b' => CountingCast::class];
}
