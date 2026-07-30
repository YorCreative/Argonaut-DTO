<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Holds a byte sequence that is not valid UTF-8, so json_encode() fails.
 */
class BadUtf8DTO extends ArgonautDTO
{
    public string $raw = "\xB1\x31";
}
