<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Shared shape for the re-entrancy fixtures: 'b' is both a property and an
 * incoming alias for 'c', so a key that is mapped twice lands somewhere
 * visibly wrong instead of failing silently.
 */
abstract class ReentrantMapBaseDTO extends ArgonautDTO
{
    protected array $maps = [
        'a' => 'b',
        'b' => 'c',
        'wire' => 'derived',
    ];

    public string $b = '';

    public string $c = '';

    public string $derived = '';

    public function rules(): array
    {
        return [];
    }
}
