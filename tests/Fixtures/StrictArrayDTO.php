<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class StrictArrayDTO extends ArgonautDTO
{
    /** @var array<int, TagDTO> */
    public array $tags = [];

    /** @var array<string, mixed> */
    protected array $casts = ['tags' => [TagDTO::class]];
}
