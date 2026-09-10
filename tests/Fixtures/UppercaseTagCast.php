<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\CastsArgonautAttribute;

class UppercaseTagCast implements CastsArgonautAttribute
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
