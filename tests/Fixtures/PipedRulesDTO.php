<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Declares rules in the pipe-delimited string form.
 */
class PipedRulesDTO extends ArgonautDTO
{
    public mixed $count = null;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['count' => 'required|int'];
    }
}
