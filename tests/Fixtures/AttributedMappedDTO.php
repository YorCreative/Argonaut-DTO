<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\MapFrom;

class AttributedMappedDTO extends ArgonautDTO
{
    #[MapFrom('first_name')]
    public string $firstName = '';
}
