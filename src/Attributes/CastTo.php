<?php

namespace YorCreative\ArgonautDTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class CastTo implements CastAttribute
{
    /** @param class-string|string $type */
    public function __construct(
        public string $type,
        public bool $many = false,
    ) {}

    /** @return string|array<int, string> */
    public function toCast(): string|array
    {
        return $this->many ? [$this->type] : $this->type;
    }
}
