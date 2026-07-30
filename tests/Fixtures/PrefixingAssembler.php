<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautAssembler;

/**
 * Resolves through an instance method, so it requires an assembler instance.
 */
class PrefixingAssembler extends ArgonautAssembler
{
    public function __construct(private readonly string $prefix = 'Dr. ') {}

    public function toProfileDTO(object $input): ProfileDTO
    {
        return new ProfileDTO(['fullName' => $this->prefix.$input->name]);
    }
}
