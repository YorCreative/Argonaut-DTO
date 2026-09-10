<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\CastsArgonautAttribute;

/**
 * Declares a non-nullable parameter, so it errors loudly if the engine ever
 * hands it a null element.
 */
class NullSkippingCast implements CastsArgonautAttribute
{
    public function get(string $key, mixed $value): mixed
    {
        return strtoupper((string) $value);
    }

    public function set(string $key, mixed $value): mixed
    {
        return $value;
    }
}
