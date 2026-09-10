<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautImmutableDTO;
use YorCreative\ArgonautDTO\Attributes\MapFrom;

class ImmutableMappedDTO extends ArgonautImmutableDTO
{
    #[MapFrom('first_name')]
    public readonly string $firstName;
}
