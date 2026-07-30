<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use OutOfBoundsException;
use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Stands in for a DTO whose rules() body contains a genuine bug.
 */
class BrokenRulesDTO extends ArgonautDTO
{
    public string $name = 'Jane';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        throw new OutOfBoundsException('rules() blew up');
    }
}
