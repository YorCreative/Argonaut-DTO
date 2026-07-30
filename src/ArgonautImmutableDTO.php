<?php

namespace YorCreative\ArgonautDTO;

use ReflectionProperty;
use YorCreative\ArgonautDTO\Traits\HasCasting;
use YorCreative\ArgonautDTO\Traits\HasSerialization;
use YorCreative\ArgonautDTO\Traits\HasValidation;

abstract class ArgonautImmutableDTO implements ArgonautDTOContract
{
    use HasCasting;
    use HasSerialization;
    use HasValidation;

    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = [])
    {
        $this->initializeFromAttributes($attributes);
    }

    /** @param array<string, mixed> $attributes */
    protected function initializeFromAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, (string) $key) && ! $this->isInternalProperty((string) $key)) {
                $castValue = $value === null ? null : $this->castInputValue((string) $key, $value);
                $this->initializeReadonlyProperty((string) $key, $castValue);
            }
        }
    }

    protected function isInternalProperty(string $key): bool
    {
        return in_array($key, $this->getExcludedSerializationProperties(), true);
    }

    protected function initializeReadonlyProperty(string $key, mixed $value): void
    {
        (new ReflectionProperty($this, $key))->setValue($this, $value);
    }
}
