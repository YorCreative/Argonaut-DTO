<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Declares cast entries Argonaut cannot resolve, which must leave the input
 * untouched rather than fail.
 */
class LooseCastDTO extends ArgonautDTO
{
    public mixed $emptyCast = null;

    public mixed $unknownCast = null;

    /** @var array<string, mixed> */
    protected array $casts = [
        'emptyCast' => [],
        'unknownCast' => 'not_a_real_type',
    ];
}
