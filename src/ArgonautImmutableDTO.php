<?php

namespace YorCreative\ArgonautDTO;

use ReflectionClass;
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
     * Unlike the mutable base class this cannot be a clone-and-reassign,
     * because readonly properties cannot be reassigned once set. Instead, an
     * uninitialized instance is built through reflection, every unchanged
     * property is copied across verbatim (already cast, not reprocessed), and
     * only the given $attributes are routed through the normal
     * initializeFromAttributes() -> castInputValue() path. This mirrors the
     * mutable class's semantics — only the changes are re-applied — which
     * matters because casting is not guaranteed idempotent: a custom cast is
     * a transformation, not a guard, and a nestedAssembler expects its source
     * shape, not an already-assembled DTO.
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
        // Only the CHANGED attributes go through casting. A full-state rebuild
        // would re-run the engine over already-cast values: built-in casts
        // survive on their identity guards, but a custom cast is a
        // transformation and would apply twice, and a nestedAssembler would
        // try to re-assemble an already-assembled DTO.
        // Internal keys are dropped from the changed set: initializeFromAttributes()
        // skips them, so treating one as "changed" would stop it being copied
        // from the original and silently reset it to its declared default.
        $changed = array_filter(
            array_keys($this->mapInputKeys($attributes)),
            fn (int|string $key): bool => ! $this->isInternalProperty((string) $key),
        );

        /** @var static $copy */
        $copy = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach ($this->copyableProperties() as $property) {
            $name = $property->getName();

            if (in_array($name, $changed, true) || ! $property->isInitialized($this)) {
                continue;
            }

            $property->setValue($copy, $property->getValue($this));
        }

        $copy->initializeFromAttributes($attributes);

        return $copy;
    }

    /**
     * Every instance property on this object, including private ones declared
     * by subclasses.
     *
     * get_object_vars($this) resolves in the scope it is called from, which is
     * this class -- a private property declared on a subclass is invisible to
     * it. Copying from that list left such a property at its declared default
     * on the copy, or, for a typed property with no default, uninitialized and
     * fatal on first read. Walking the hierarchy sees all of them.
     *
     * @return list<ReflectionProperty>
     */
    private function copyableProperties(): array
    {
        $properties = [];
        $seen = [];

        for ($class = new ReflectionClass($this); $class !== false; $class = $class->getParentClass()) {
            foreach ($class->getProperties() as $property) {
                if ($property->isStatic() || isset($seen[$property->getName()])) {
                    continue;
                }

                $seen[$property->getName()] = true;
                $properties[] = $property;
            }
        }

        return $properties;
    }
}
