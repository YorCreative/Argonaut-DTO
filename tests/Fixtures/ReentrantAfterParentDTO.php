<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/** Touches another attribute directly, after delegating. */
class ReentrantAfterParentDTO extends ReentrantMapBaseDTO
{
    public function setAttribute(string $key, mixed $value): static
    {
        $result = parent::setAttribute($key, $value);

        if ($key === 'b' && $this->derived === '') {
            $this->setMappedAttribute('wire', 'nested');
        }

        return $result;
    }
}
