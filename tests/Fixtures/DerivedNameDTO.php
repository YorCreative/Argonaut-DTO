<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Derives fullName from two setters.
 *
 * This fixture exists to catch a specific defect: a with() implemented as a
 * full-state rebuild recomputes fullName in the prioritized pass and then
 * clobbers it with the stale value in the remaining pass.
 */
class DerivedNameDTO extends ArgonautDTO
{
    public string $firstName = '';

    public string $lastName = '';

    public string $fullName = '';

    /** @var list<string> */
    protected array $prioritizedAttributes = ['firstName', 'lastName'];

    public function setFirstName(string $value): static
    {
        $this->firstName = $value;
        $this->fullName = trim($value.' '.$this->lastName);

        return $this;
    }

    public function setLastName(string $value): static
    {
        $this->lastName = $value;
        $this->fullName = trim($this->firstName.' '.$value);

        return $this;
    }
}
