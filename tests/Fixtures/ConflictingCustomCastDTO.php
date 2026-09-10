<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\CastWith;

/** $casts must win over the attribute, matching the rule already shipped. */
class ConflictingCustomCastDTO extends ArgonautDTO
{
    #[CastWith(UppercaseCast::class)]
    public mixed $name = null;

    /** @var array<string, string> */
    protected array $casts = ['name' => 'string'];
}
