<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Nests through an array cast, so every DTO level costs two array levels once
 * serialized. Used to prove the walk limit and json_encode()'s own limit do
 * not disagree.
 */
class BranchDTO extends ArgonautDTO
{
    public string $label = '';

    /** @var array<int, BranchDTO> */
    public array $children = [];

    /** @var array<string, mixed> */
    protected array $casts = ['children' => [BranchDTO::class]];

    public static function nest(int $levels): self
    {
        $attributes = ['label' => 'L'.$levels];

        for ($level = $levels - 1; $level >= 1; $level--) {
            $attributes = ['label' => 'L'.$level, 'children' => [$attributes]];
        }

        return new self($attributes);
    }
}
