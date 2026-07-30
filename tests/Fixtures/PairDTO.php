<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Holds two independent slots so the same DTO can appear in sibling branches
 * without that being a circular reference.
 */
class PairDTO extends ArgonautDTO
{
    public ?NodeDTO $left = null;

    public ?NodeDTO $right = null;

    /** @var array<string, string> */
    protected array $casts = [
        'left' => NodeDTO::class,
        'right' => NodeDTO::class,
    ];
}
