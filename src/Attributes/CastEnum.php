<?php

namespace YorCreative\ArgonautDTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class CastEnum implements CastAttribute
{
    /** @param class-string<\BackedEnum>|string $enum */
    public function __construct(public string $enum) {}

    public function toCast(): string
    {
        return $this->enum;
    }
}
