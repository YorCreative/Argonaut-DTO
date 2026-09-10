<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * A private property deliberately exposed through working magic accessors.
 */
class MagicAccessorDTO extends ArgonautDTO
{
    private string $hidden = 'magic-value';

    public function __isset(string $name): bool
    {
        return $name === 'hidden';
    }

    public function __get(string $name): mixed
    {
        return $name === 'hidden' ? $this->hidden : null;
    }

    public function rules(): array
    {
        return [];
    }
}
