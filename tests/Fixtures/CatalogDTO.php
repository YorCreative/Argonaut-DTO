<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Collection;

/**
 * Exercises the `collection:` shorthand, array-of-enum casts and scalar casts.
 */
class CatalogDTO extends ArgonautDTO
{
    public Collection $tags;

    /** @var array<int, Status> */
    public array $statuses = [];

    public int $total = 0;

    public bool $active = false;

    public float $rating = 0.0;

    /** @var array<string, mixed> */
    protected array $casts = [
        'tags' => 'collection:'.TagDTO::class,
        'statuses' => [Status::class],
        'total' => 'integer',
        'active' => 'boolean',
        'rating' => 'float',
    ];
}
