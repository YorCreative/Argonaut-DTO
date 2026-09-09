<?php

namespace YorCreative\ArgonautDTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class MapFrom
{
    public function __construct(public string $from) {}
}
