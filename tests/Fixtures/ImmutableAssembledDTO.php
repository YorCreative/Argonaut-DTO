<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautImmutableDTO;

class ImmutableAssembledDTO extends ArgonautImmutableDTO
{
    public readonly ProfileDTO $profile;

    /** @var array<string, string> */
    protected array $casts = ['profile' => ProfileDTO::class];

    /** @var array<string, class-string> */
    protected array $nestedAssemblers = ['profile' => ProfileAssembler::class];
}
