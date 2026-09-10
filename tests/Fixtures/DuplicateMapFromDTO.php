<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\MapFrom;

/**
 * Two properties claiming the same incoming key. Only one can win, so the
 * declaration is ambiguous and must be rejected.
 */
class DuplicateMapFromDTO extends ArgonautDTO
{
    #[MapFrom('key')]
    public string $a = '';

    #[MapFrom('key')]
    public string $b = '';

    public function rules(): array
    {
        return [];
    }
}
