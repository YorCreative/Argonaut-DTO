<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautImmutableDTO;

class ImmutablePointDTO extends ArgonautImmutableDTO
{
    public readonly int $x;

    public readonly int $y;
}
