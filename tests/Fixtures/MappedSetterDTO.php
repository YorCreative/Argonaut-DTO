<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Carries a chained map (a -> b, b -> c) to prove input keys are mapped once
 * rather than walked through successive hops.
 */
class MappedSetterDTO extends ArgonautDTO
{
    protected array $maps = [
        'first_name' => 'firstName',
        'a' => 'b',
        'b' => 'c',
    ];

    public string $firstName = '';

    public string $b = '';

    public string $c = '';

    public function rules(): array
    {
        return [];
    }
}
