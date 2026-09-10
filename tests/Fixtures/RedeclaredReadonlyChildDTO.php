<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/**
 * Redeclares a public readonly property. Unlike a private one, this shares the
 * parent's storage -- there is a single slot, and writing it twice fails.
 */
class RedeclaredReadonlyChildDTO extends RedeclaredReadonlyParentDTO
{
    public readonly string $shared;
}
