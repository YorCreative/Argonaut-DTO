<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\MapFrom;

/** $maps must win over the attribute. */
class ConflictingMappedDTO extends ArgonautDTO
{
    #[MapFrom('attr_key')]
    public string $value = '';

    /** @var array<string, string> */
    protected array $maps = ['array_key' => 'value'];
}
