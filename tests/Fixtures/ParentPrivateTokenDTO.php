<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautImmutableDTO;

/**
 * Declares a private $token with no default. The child below declares its own
 * private $token: two distinct slots that happen to share a name.
 */
class ParentPrivateTokenDTO extends ArgonautImmutableDTO
{
    // Deliberately not readonly: PHP 8.3 refuses to initialize a
    // parent-declared readonly property from a subclass scope, which is a
    // separate limitation from the private-slot behaviour under test here.
    public string $label = '';

    private string $token;

    public function stampParent(string $value): void
    {
        $this->token = $value;
    }

    public function parentToken(): string
    {
        return $this->token;
    }

    public function rules(): array
    {
        return [];
    }
}
