<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class MappedDTO extends ArgonautDTO
{
    public string $firstName = '';

    public string $lastName = '';

    /** @var array<string, string> incoming key => property name */
    protected array $maps = ['first_name' => 'firstName', 'last_name' => 'lastName'];
}
