<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * A self-nesting DTO used to exercise serialization depth.
 */
class NodeDTO extends ArgonautDTO
{
    public string $label;

    public ?NodeDTO $child = null;

    /** @var array<string, string> */
    protected array $casts = [
        'label' => 'string',
        'child' => NodeDTO::class,
    ];

    /**
     * Build a chain of $levels nodes labelled L1..L{$levels}.
     */
    public static function chain(int $levels): self
    {
        $attributes = ['label' => 'L'.$levels];

        for ($level = $levels - 1; $level >= 1; $level--) {
            $attributes = ['label' => 'L'.$level, 'child' => $attributes];
        }

        return new self($attributes);
    }
}
