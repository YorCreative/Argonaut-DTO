<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Counts rules() invocations so a test can prove validateAll() does not
 * validate the same item twice.
 */
class CountingRulesDTO extends ArgonautDTO
{
    public static int $ruleCalls = 0;

    public ?string $email = null;

    public function rules(): array
    {
        self::$ruleCalls++;

        return ['email' => 'required|email'];
    }
}
