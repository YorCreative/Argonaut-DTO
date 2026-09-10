<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/** A child cannot re-attribute an inherited property; $casts is its only lever. */
class OverridingChildDTO extends AttributedParentDTO
{
    /** @var array<string, string> */
    protected array $casts = ['thing' => 'string'];
}
