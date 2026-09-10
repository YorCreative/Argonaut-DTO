<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\CastTo;

class AttributedParentDTO extends ArgonautDTO
{
    #[CastTo(TagDTO::class)]
    public mixed $thing = null;
}
