<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Overrides setAttribute() so every input path can be checked for override
 * dispatch, and carries a chained map (a -> b, b -> c) plus a prioritized
 * property reached through a mapped key.
 */
class UppercasingSetterDTO extends ArgonautDTO
{
    protected array $maps = [
        'a' => 'b',
        'b' => 'c',
        'source_key' => 'source',
    ];

    protected array $prioritizedAttributes = ['source'];

    public string $name = '';

    public string $b = '';

    public string $c = '';

    public string $source = '';

    public string $dependent = '';

    /** @var list<string> */
    public array $assignmentOrder = [];

    public function setAttribute(string $key, mixed $value): static
    {
        if ($key === 'name' && is_string($value)) {
            $value = strtoupper($value);
        }

        return parent::setAttribute($key, $value);
    }

    public function setSource(string $value): void
    {
        $this->source = $value;
        $this->assignmentOrder[] = 'source';
    }

    public function setDependent(string $value): void
    {
        $this->dependent = $value;
        $this->assignmentOrder[] = 'dependent';
    }

    public function rules(): array
    {
        return [];
    }
}
