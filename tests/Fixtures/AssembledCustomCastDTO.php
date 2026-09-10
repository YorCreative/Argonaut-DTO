<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class AssembledCustomCastDTO extends ArgonautDTO
{
    public mixed $single = null;

    /** @var array<int, mixed> */
    public array $many = [];

    public mixed $collection = null;

    /** @var array<string, mixed> */
    protected array $casts = [
        'single' => UppercaseCast::class,
        'many' => [UppercaseCast::class],
        'collection' => 'collection:'.UppercaseCast::class,
    ];

    /** @var array<string, class-string> */
    protected array $nestedAssemblers = [
        'single' => ProfileAssembler::class,
        'many' => ProfileAssembler::class,
        'collection' => ProfileAssembler::class,
    ];
}
