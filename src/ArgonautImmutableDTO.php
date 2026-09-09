<?php

namespace YorCreative\ArgonautDTO;

use ReflectionProperty;
use YorCreative\ArgonautDTO\Traits\HasCasting;
use YorCreative\ArgonautDTO\Traits\HasFactories;
use YorCreative\ArgonautDTO\Traits\HasKeyMapping;
use YorCreative\ArgonautDTO\Traits\HasSerialization;
use YorCreative\ArgonautDTO\Traits\HasValidation;

abstract class ArgonautImmutableDTO implements ArgonautDTOContract
{
    use HasCasting;
    use HasFactories;
    use HasKeyMapping;
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
        $attributes = $this->mapInputKeys($attributes);

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

    /**
     * Return a copy with the given attributes applied.
     *
     * Unlike the mutable base class this rebuilds from full state, because
     * readonly properties cannot be reassigned on a clone. That is safe here:
     * initializeFromAttributes() writes through reflection and never dispatches
     * setters, so there is no derived-property pass to clobber.
     *
     * This is a shallow copy: nested objects are shared with the original, not
     * duplicated. Mutating a nested DTO reached through the copy therefore
     * mutates the original too. Rebuild nested values explicitly if you need
     * them independent.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function with(array $attributes): static
    {
        return new static(array_merge($this->rawAttributes(), $attributes));
    }

    /**
     * The current attribute values.
     *
     * Internal properties (casts, nestedAssemblers, prioritizedAttributes) are
     * not filtered here: initializeFromAttributes() already skips them via
     * isInternalProperty(), which reads the same exclusion list. If this helper
     * ever gains a caller that does NOT go through the constructor, that caller
     * must do its own filtering.
     *
     * @return array<string, mixed>
     */
    private function rawAttributes(): array
    {
        return get_object_vars($this);
    }
}
