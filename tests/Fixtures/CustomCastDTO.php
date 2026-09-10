<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class CustomCastDTO extends ArgonautDTO
{
    public mixed $name = null;

    /** @var array<string, string> */
    protected array $casts = ['name' => UppercaseCast::class];
}
