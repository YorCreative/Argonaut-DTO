<?php

namespace YorCreative\ArgonautDTO;

use YorCreative\ArgonautDTO\Traits\HasCasting;
use YorCreative\ArgonautDTO\Traits\HasFactories;
use YorCreative\ArgonautDTO\Traits\HasKeyMapping;
use YorCreative\ArgonautDTO\Traits\HasSerialization;
use YorCreative\ArgonautDTO\Traits\HasValidation;

class ArgonautDTO implements ArgonautDTOContract
{
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

        // Dispatch is then plain: setAttribute() takes property names and never
        // maps, so an override runs once per assignment, under the name it
        // declared, and nothing it does from inside can be mistaken for part of
        // the surrounding bulk operation.
        foreach ($this->prioritizedAttributes as $key) {
            if (array_key_exists($key, $attributes)) {
                $this->setAttribute((string) $key, $attributes[$key]);
                unset($attributes[$key]);
            }
        }

        foreach ($attributes as $key => $value) {
            $this->setAttribute((string) $key, $value);
        }

        return $this;
    }

    /**
     * Set one attribute by its PROPERTY NAME.
     *
     * Key mapping is not applied here. This is the assignment seam every input
     * path funnels through -- the constructor, setAttributes(), merge() and
     * with() all map their input first and then dispatch canonical property
     * names to it -- so a subclass override sees each assignment exactly once,
     * under the name it declared, and may safely reach for other attributes
     * from inside it.
     *
     * Use setMappedAttribute() to assign by an incoming alias.
     */
    public function setAttribute(string $key, mixed $value): static
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

    /**
     * Set one attribute by an incoming key, applying key mapping first.
     *
     * The single-attribute counterpart to setAttributes(): the key is resolved
     * through $maps and #[MapFrom] and then handed to setAttribute() under its
     * property name. A key with no mapping is passed through unchanged.
     *
     * This is NOT interchangeable with setAttribute(). A property name can
     * itself be an incoming alias -- with $maps = ['a' => 'b', 'b' => 'c'],
     * property `b` is also the alias for `c`, so setMappedAttribute('b')
     * assigns `c`, not `b`. Call setAttribute() when you mean the property.
     */
    public function setMappedAttribute(string $key, mixed $value): static
    {
        $maps = $this->keyMaps();

        return $this->setAttribute((string) ($maps[$key] ?? $key), $value);
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
