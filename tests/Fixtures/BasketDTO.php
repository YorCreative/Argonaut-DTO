<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Lets a DTO be placed inside its own array property.
 */
class BasketDTO extends ArgonautDTO
{
    public string $label = '';

    /** @var array<int, mixed> */
    public array $items = [];
}
