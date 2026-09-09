<?php

namespace YorCreative\ArgonautDTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class CastWith implements CastAttribute
{
    /** @param class-string $cast */
    public function __construct(
        public string $cast,
        public bool $many = false,
    ) {}

    /** @return string|array<int, string> */
    public function toCast(): string|array
    {
        return $this->many ? [$this->cast] : $this->cast;
    }
}
