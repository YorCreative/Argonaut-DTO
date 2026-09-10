<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * $maps renames fullName to the incoming key "name", while "name" is also a
 * real property. On output both resolve to the same key.
 */
class CollidingInverseMapDTO extends ArgonautDTO
{
    protected array $maps = ['name' => 'fullName'];

    public ?string $fullName = null;

    public ?string $name = null;

    public function rules(): array
    {
        return [];
    }
}
