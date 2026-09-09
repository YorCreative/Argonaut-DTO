<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\CastsArgonautAttribute;

/**
 * Uppercases a string. Stateless, as every cast must be.
 */
class UppercaseCast implements CastsArgonautAttribute
{
    public function get(string $key, mixed $value, bool $withKey = false): mixed
    {
        $cast = is_string($value) ? strtoupper($value) : $value;

        return $withKey ? $key.':'.$cast : $cast;
    }
}
