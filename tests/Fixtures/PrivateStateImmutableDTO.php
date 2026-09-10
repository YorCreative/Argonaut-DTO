<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautImmutableDTO;

/**
 * Holds subclass-private state that the parent's scope cannot see, in both
 * defaulted and undefaulted form.
 */
class PrivateStateImmutableDTO extends ArgonautImmutableDTO
{
    public readonly string $label;

    private string $secret = 'default';

    /** Typed, private, and deliberately without a default. */
    private string $token;

    public function stamp(string $value): void
    {
        $this->secret = $value;
    }

    public function tag(string $value): void
    {
        $this->token = $value;
    }

    public function secret(): string
    {
        return $this->secret;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function rules(): array
    {
        return [];
    }
}
