<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautImmutableDTO;

class ImmutableCustomCastDTO extends ArgonautImmutableDTO
{
    public readonly mixed $amount;

    public readonly string $label;

    /** @var array<string, string> */
    protected array $casts = ['amount' => HalvingCast::class];
}
