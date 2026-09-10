<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/** The same override, reached through the custom-cast collection path. */
class OverriddenFactoryCustomCastDTO extends OverriddenFactoryDTO
{
    protected array $casts = ['tags' => 'collection:'.UppercaseTagCast::class];
}
