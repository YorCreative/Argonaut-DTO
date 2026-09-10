<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautImmutableDTO;

class RuntimeCastsImmutableDTO extends ArgonautImmutableDTO
{
    public readonly string $label;

    /** @param array<string, string> $casts */
    public function configure(array $casts): void
    {
        $this->casts = $casts;
    }

    /** @return array<string, string|array<int, string>> */
    public function currentCasts(): array
    {
        return $this->casts;
    }

    public function rules(): array
    {
        return [];
    }
}
