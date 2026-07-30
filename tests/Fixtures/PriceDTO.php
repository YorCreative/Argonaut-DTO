<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class PriceDTO extends ArgonautDTO
{
    public ?Money $total = null;

    /** @var array<string, string> */
    protected array $casts = ['total' => Money::class];
}
