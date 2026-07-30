<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautAssembler;

class ProfileAssembler extends ArgonautAssembler
{
    public static function toProfileDTO(object $input): ProfileDTO
    {
        return new ProfileDTO(['fullName' => $input->first.' '.$input->last]);
    }
}
