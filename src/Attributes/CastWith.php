<?php

namespace YorCreative\ArgonautDTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class CastWith implements CastAttribute
{
    /** @param class-string $cast */
    public function __construct(public string $cast) {}

    public function toCast(): string
    {
        return $this->cast;
    }
}
