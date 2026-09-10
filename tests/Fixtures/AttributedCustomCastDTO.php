<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\CastWith;

class AttributedCustomCastDTO extends ArgonautDTO
{
    #[CastWith(UppercaseCast::class)]
    public mixed $name = null;
}
