<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Pairs a nested assembler with a scalar cast, so the assembler has no target
 * class to build.
 */
class UnresolvableAssemblerDTO extends ArgonautDTO
{
    public string $label = '';

    /** @var array<string, string> */
    protected array $casts = ['label' => 'string'];

    /** @var array<string, class-string> */
    protected array $nestedAssemblers = ['label' => ProfileAssembler::class];
}
