<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/** Touches another attribute directly, before delegating. */
class ReentrantBeforeParentDTO extends ReentrantMapBaseDTO
{
    public function setAttribute(string $key, mixed $value): static
    {
        if ($key === 'b' && $this->derived === '') {
            $this->setMappedAttribute('wire', 'nested');
        }

        return parent::setAttribute($key, $value);
    }
}
