<?php

namespace YorCreative\ArgonautDTO\Attributes;

/**
 * A property attribute that compiles to a value the $casts array already
 * understands. Keeping attributes in this shape means the casting engine
 * never learns they exist.
 */
interface CastAttribute
{
    /** @return string|array<int, string> */
    public function toCast(): string|array;
}
