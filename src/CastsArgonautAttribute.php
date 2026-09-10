<?php

namespace YorCreative\ArgonautDTO;

/**
 * A consumer-owned transformation applied when hydrating an attribute.
 *
 * Distinct from the cast attributes in src/Attributes/: those DECLARE which
 * built-in cast applies, while this PERFORMS a conversion the library does not
 * know how to do.
 *
 * Implementations MUST be stateless and constructible with no arguments.
 * Instances are cached and reused per cast class, so the same instance may
 * serve many DTOs; a cast that keeps state between calls will leak it.
 *
 * A cast never receives null: setAttribute() short-circuits null before casting.
 */
interface CastsArgonautAttribute
{
    public function get(string $key, mixed $value): mixed;
}
