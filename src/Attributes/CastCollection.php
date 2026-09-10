<?php

namespace YorCreative\ArgonautDTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class CastCollection implements CastAttribute
{
    /** @param class-string|string $type */
    public function __construct(public string $type) {}

    public function toCast(): string
    {
        return 'collection:'.$this->type;
    }
}
