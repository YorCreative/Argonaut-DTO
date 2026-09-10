<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class BulkNullCastDTO extends ArgonautDTO
{
    protected array $casts = ['tags' => [NullSkippingCast::class]];

    /** @var array<int, mixed> */
    public array $tags = [];

    public function rules(): array
    {
        return [];
    }
}
