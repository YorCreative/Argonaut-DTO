<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautImmutableDTO;

class RedeclaredReadonlyParentDTO extends ArgonautImmutableDTO
{
    public readonly string $shared;

    public readonly string $other;

    public function rules(): array
    {
        return [];
    }
}
