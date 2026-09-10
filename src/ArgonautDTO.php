<?php

namespace YorCreative\ArgonautDTO;

use WeakMap;
use YorCreative\ArgonautDTO\Traits\HasCasting;
use YorCreative\ArgonautDTO\Traits\HasFactories;
use YorCreative\ArgonautDTO\Traits\HasKeyMapping;
use YorCreative\ArgonautDTO\Traits\HasSerialization;
use YorCreative\ArgonautDTO\Traits\HasValidation;

class ArgonautDTO implements ArgonautDTOContract
{
    /**
     * Objects currently inside setAttributes().
     *
     * Static, and therefore invisible to get_object_vars(), so it neither
     * appears in serialized output nor reserves another property name on
     * subclasses. A WeakMap keeps no object alive, matching the guard
     * HasSerialization already uses.
     *
     * @var WeakMap<object, true>|null
     */
    private static ?WeakMap $bulkAssignmentGuard = null;

    use HasCasting;
    use HasFactories;
    use HasKeyMapping;
    use HasSerialization;
    use HasValidation;

    /** @var array<class-string, array<string, string|false>> */
    protected static array $setterMap = [];

    /** @var list<string> */
    protected array $prioritizedAttributes = [];

    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function setAttributes(array $attributes): static
    {
        // Mapping runs once here, over the whole input. That is also where a
        // collision is settled -- when an alias and its target property both
        // appear, the loser is dropped while it is still just an array key, so
        // an invalid losing value is never assigned to a typed property.
        $attributes = $this->mapInputKeys($attributes);

        // setAttribute() is still the assignment seam, so a subclass override
        // runs for bulk input too, and it receives canonical property names --
        // an override that normalises per property cannot do so if it is handed
        // an alias. The guard below stops it mapping a second time, which would
        // walk a chained map (a -> b, b -> c) an extra hop.
        $guard = self::$bulkAssignmentGuard ??= new WeakMap;
        $alreadyGuarded = isset($guard[$this]);
        $guard[$this] = true;

        try {
            foreach ($this->prioritizedAttributes as $key) {
                if (array_key_exists($key, $attributes)) {
                    $this->setAttribute((string) $key, $attributes[$key]);
                    unset($attributes[$key]);
                }
            }

            foreach ($attributes as $key => $value) {
                $this->setAttribute((string) $key, $value);
            }
        } finally {
            // A nested setAttributes() -- one reached from inside an override --
            // must leave the outer call's guard standing.
            if (! $alreadyGuarded) {
                unset($guard[$this]);
            }
        }

        return $this;
    }

    /**
     * Set one attribute, applying key mapping first.
     *
     * Every other input path -- the constructor, setAttributes(), merge() --
     * maps incoming keys before assigning, and this is an input path too. It
     * used to assign the raw key, so a mapped key silently matched no property
     * and the call did nothing.
     */
    public function setAttribute(string $key, mixed $value): static
    {
        // Inside setAttributes() the key is already canonical: mapping ran once
        // over the whole array, which is where collisions were settled too.
        if (! $this->insideBulkAssignment()) {
            $maps = $this->keyMaps();
            $key = (string) ($maps[$key] ?? $key);
        }

        return $this->assignAttribute($key, $value);
    }

    private function insideBulkAssignment(): bool
    {
        return self::$bulkAssignmentGuard !== null && isset(self::$bulkAssignmentGuard[$this]);
    }

    /**
     * Assign an attribute by property name, with no key mapping applied.
     */
    private function assignAttribute(string $key, mixed $value): static
    {
        $class = static::class;
        static::$setterMap[$class] ??= [];

        if (array_key_exists($key, static::$setterMap[$class])) {
            $method = static::$setterMap[$class][$key];
        } else {
            $candidate = 'set'.ucfirst(str_replace(['-', '_', ' '], '', ucwords($key, '-_ ')));
            $method = method_exists($this, $candidate) ? $candidate : false;
            static::$setterMap[$class][$key] = $method;
        }

        if ($method !== false) {
            $this->{$method}($value);
        } elseif (property_exists($this, $key)) {
            $this->{$key} = $value === null ? null : $this->castInputValue($key, $value);
        }

        return $this;
    }

    /** @param array<string, mixed> $attributes */
    public function merge(array $attributes): static
    {
        return $this->setAttributes($attributes);
    }

    /**
     * Return a copy with the given attributes applied.
     *
     * The non-mutating twin of merge(). Only the changes are passed to
     * setAttributes(), so setter-derived properties recompute correctly; a
     * full-state rebuild would recompute them in the prioritized pass and then
     * clobber them with stale values in the remaining pass.
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
        return (clone $this)->setAttributes($attributes);
    }

    /** @return array<string, mixed> */
    public function getAttributesToUpdate(): array
    {
        $attributes = get_object_vars($this);

        foreach ($this->getExcludedSerializationProperties() as $property) {
            unset($attributes[$property]);
        }

        return $attributes;
    }
}
