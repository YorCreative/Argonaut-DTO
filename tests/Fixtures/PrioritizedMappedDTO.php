<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Declares $prioritizedAttributes AND $maps over the same properties.
 *
 * This fixture exists to pin the ordering guarantee: mapInputKeys() must run
 * BEFORE the prioritized pass, or the mapped keys never match it.
 */
class PrioritizedMappedDTO extends ArgonautDTO
{
    public string $firstName = '';

    public string $lastName = '';

    public string $fullName = '';

    /** @var list<string> */
    protected array $prioritizedAttributes = ['firstName', 'lastName'];

    /** @var array<string, string> */
    protected array $maps = ['first_name' => 'firstName', 'last_name' => 'lastName'];

    public function setFirstName(string $value): static
    {
        $this->firstName = $value;

        return $this;
    }

    /**
     * Deliberately one-directional: fullName is derived here, from whatever
     * firstName currently holds, and nowhere else. This is why firstName is
     * listed ahead of lastName in $prioritizedAttributes — the ordering
     * guarantee is what makes firstName reliably already set by the time
     * this runs.
     */
    public function setLastName(string $value): static
    {
        $this->lastName = $value;
        $this->fullName = trim($this->firstName.' '.$value);

        return $this;
    }
}
