<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

class ChildPrivateTokenDTO extends ParentPrivateTokenDTO
{
    private string $token;

    public function stampChild(string $value): void
    {
        $this->token = $value;
    }

    public function childToken(): string
    {
        return $this->token;
    }
}
