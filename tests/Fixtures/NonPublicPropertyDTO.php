<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class NonPublicPropertyDTO extends ArgonautDTO
{
    public string $shown = 'shown';

    protected string $secret = 'hidden';

    public function rules(): array
    {
        return [];
    }
}
