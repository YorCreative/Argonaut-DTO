<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Records the keys its overridden setAttribute() is handed, and normalises by
 * canonical property name -- which only works if it receives canonical names.
 */
class RecordingSetterDTO extends ArgonautDTO
{
    protected array $maps = [
        'wire' => 'count',
        'label_alias' => 'label',
        // 'label' is itself an alias for another property, so a key that is
        // mapped a second time lands in $shadow where a test can see it.
        'label' => 'shadow',
    ];

    public int $count = 0;

    public string $label = '';

    public string $shadow = '';

    /** @var list<string> */
    public array $seenKeys = [];

    public function setAttribute(string $key, mixed $value): static
    {
        $this->seenKeys[] = $key;

        if ($key === 'label' && is_string($value)) {
            $value = trim($value);
        }

        return parent::setAttribute($key, $value);
    }

    public function rules(): array
    {
        return [];
    }
}
