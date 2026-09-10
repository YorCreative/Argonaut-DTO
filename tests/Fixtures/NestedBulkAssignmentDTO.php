<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * An override that itself calls setAttributes(), nesting bulk assignment. The
 * inner call must not tear down the outer call's mapping guard.
 */
class NestedBulkAssignmentDTO extends ArgonautDTO
{
    protected array $maps = [
        'alias' => 'primary',
        'a' => 'b',
        'b' => 'c',
    ];

    public string $primary = '';

    public string $derived = '';

    public string $b = '';

    public string $c = '';

    public function setAttribute(string $key, mixed $value): static
    {
        parent::setAttribute($key, $value);

        // Re-enters setAttributes() from inside a bulk assignment.
        if ($key === 'primary' && $this->derived === '') {
            $this->setAttributes(['derived' => 'from-'.$value, 'a' => 'chained']);
        }

        return $this;
    }

    public function rules(): array
    {
        return [];
    }
}
