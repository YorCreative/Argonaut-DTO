<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

class EmailDTO extends ArgonautDTO
{
    public string $email;

    /** @var array<string, string> */
    protected array $casts = ['email' => 'string'];

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['email' => ['required', 'email']];
    }
}
