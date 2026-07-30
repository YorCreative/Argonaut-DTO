<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Left uncast on purpose so raw input reaches the validator and the Argonaut
 * `int` / `bool` rule aliases are what is under test.
 */
class AliasRulesDTO extends ArgonautDTO
{
    public mixed $count = null;

    public mixed $flag = null;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'count' => ['int'],
            'flag' => ['bool'],
        ];
    }
}
