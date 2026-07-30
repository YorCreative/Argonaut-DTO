<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class TagDTO extends ArgonautDTO
{
    public string $name;

    /** @var array<string, string> */
    protected array $casts = ['name' => 'string'];
}
