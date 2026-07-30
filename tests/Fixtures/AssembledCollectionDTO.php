<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Collection;

/**
 * Routes a `collection:` shorthand cast through a nested assembler.
 */
class AssembledCollectionDTO extends ArgonautDTO
{
    public Collection $profiles;

    /** @var array<string, string> */
    protected array $casts = ['profiles' => 'collection:'.ProfileDTO::class];

    /** @var array<string, class-string> */
    protected array $nestedAssemblers = ['profiles' => ProfileAssembler::class];
}
