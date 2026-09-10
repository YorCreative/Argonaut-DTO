<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Collection;

class BulkNullCollectionCastDTO extends ArgonautDTO
{
    protected array $casts = ['tags' => 'collection:'.NullSkippingCast::class];

    public ?Collection $tags = null;

    public function rules(): array
    {
        return [];
    }
}
