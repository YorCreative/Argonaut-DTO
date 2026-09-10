<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * A setter that reaches for a second attribute by its incoming alias. The
 * nested call is a direct one and must be mapped normally, even though the
 * assignment that triggered it arrived through bulk input.
 */
class NestedAliasSetterDTO extends ArgonautDTO
{
    protected array $maps = [
        'wire' => 'derived',
        'source_key' => 'source',
    ];

    public string $source = '';

    public string $derived = '';

    public function setSource(string $value): void
    {
        $this->source = $value;
        $this->setMappedAttribute('wire', 'from-'.$value);
    }

    public function rules(): array
    {
        return [];
    }
}
