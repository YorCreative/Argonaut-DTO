<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Routes nested input through an assembler before casting.
 */
class AccountDTO extends ArgonautDTO
{
    public ?ProfileDTO $owner = null;

    /** @var array<int, ProfileDTO> */
    public array $members = [];

    /** @var array<string, mixed> */
    protected array $casts = [
        'owner' => ProfileDTO::class,
        'members' => [ProfileDTO::class],
    ];

    /** @var array<string, class-string> */
    protected array $nestedAssemblers = [
        'owner' => ProfileAssembler::class,
        'members' => ProfileAssembler::class,
    ];
}
