<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Publishes two private properties through magic accessors, one of which holds
 * null. A conventional __isset() reports false for the null one.
 */
class NullableMagicAccessorDTO extends ArgonautDTO
{
    private ?string $optional = null;

    private ?string $present = 'value';

    public function __isset(string $name): bool
    {
        return in_array($name, ['optional', 'present'], true) && isset($this->{$name});
    }

    public function __get(string $name): mixed
    {
        return in_array($name, ['optional', 'present'], true) ? $this->{$name} : null;
    }

    public function rules(): array
    {
        return [];
    }
}
