<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class NumericMapDTO extends ArgonautDTO
{
    protected array $maps = ['123' => 'code'];

    public string $code = '';

    public function rules(): array
    {
        return [];
    }
}
