<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/** Re-enters setAttributes(), before delegating. */
class ReentrantBulkBeforeParentDTO extends ReentrantMapBaseDTO
{
    public function setAttribute(string $key, mixed $value): static
    {
        if ($key === 'b' && $this->derived === '') {
            $this->setAttributes(['wire' => 'nested']);
        }

        return parent::setAttribute($key, $value);
    }
}
