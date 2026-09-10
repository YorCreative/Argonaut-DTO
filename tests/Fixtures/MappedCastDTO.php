<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class MappedCastDTO extends ArgonautDTO
{
    public string $firstName = '';

    /** @var array<string, string> */
    protected array $maps = ['first_name' => 'firstName'];

    /** @var array<string, string> */
    protected array $casts = ['firstName' => 'string'];
}
