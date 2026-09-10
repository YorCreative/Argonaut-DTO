<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

/** Re-enters setAttributes(), after delegating. */
class ReentrantBulkAfterParentDTO extends ReentrantMapBaseDTO
{
    public function setAttribute(string $key, mixed $value): static
    {
        $result = parent::setAttribute($key, $value);

        if ($key === 'b' && $this->derived === '') {
            $this->setAttributes(['wire' => 'nested']);
        }

        return $result;
    }
}
