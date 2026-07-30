<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautAssembler;

/**
 * Only declares the from<Class> form, exercising the fallback resolution.
 */
class LegacyAssembler extends ArgonautAssembler
{
    public static function fromProfileDTO(object $input): ProfileDTO
    {
        return new ProfileDTO(['fullName' => $input->name]);
    }
}
