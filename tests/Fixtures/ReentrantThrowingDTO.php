<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use RuntimeException;

/** A nested call that throws, so cleanup of pending state can be checked. */
class ReentrantThrowingDTO extends ReentrantMapBaseDTO
{
    public bool $allowNested = true;

    public function setAttribute(string $key, mixed $value): static
    {
        if ($key === 'b' && $this->allowNested) {
            throw new RuntimeException('nested failure');
        }

        return parent::setAttribute($key, $value);
    }
}
