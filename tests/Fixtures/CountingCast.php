<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\CastsArgonautAttribute;

/**
 * Counts invocations on the instance, so a test can prove one instance is
 * reused rather than a new one being constructed per hydration.
 */
class CountingCast implements CastsArgonautAttribute
{
    /** Counts how many times this cast class has been CONSTRUCTED. */
    public static int $instantiations = 0;

    public function __construct()
    {
        self::$instantiations++;
    }

    public function get(string $key, mixed $value): mixed
    {
        return $value;
    }
}
