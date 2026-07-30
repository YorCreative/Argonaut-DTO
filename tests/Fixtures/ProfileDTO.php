<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class ProfileDTO extends ArgonautDTO
{
    public string $fullName;

    /** @var array<string, string> */
    protected array $casts = ['fullName' => 'string'];
}
