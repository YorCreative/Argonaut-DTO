<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * A DTO that never declares rules(), so validation is a programming error.
 */
class NoRulesDTO extends ArgonautDTO
{
    public string $name = 'Jane';
}
