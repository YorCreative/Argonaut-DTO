<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\CastWith;

class AttributedArrayCustomCastDTO extends ArgonautDTO
{
    /** @var array<int, mixed> */
    #[CastWith(UppercaseCast::class, many: true)]
    public array $tags = [];
}
