<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\CastsArgonautAttribute;

/**
 * Divides a numeric value by 2. Deliberately NOT idempotent: applying it
 * twice silently produces a quarter of the original value instead of an
 * exception, which is exactly the kind of corruption an unguarded
 * immutable with() rebuild would cause on a custom cast.
 */
class HalvingCast implements CastsArgonautAttribute
{
    public function get(string $key, mixed $value, bool $withKey = false): mixed
    {
        return $value / 2;
    }
}
