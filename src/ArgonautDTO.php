<?php

namespace YorCreative\ArgonautDTO;

use YorCreative\ArgonautDTO\Traits\HasCasting;
use YorCreative\ArgonautDTO\Traits\HasSerialization;
use YorCreative\ArgonautDTO\Traits\HasValidation;

class ArgonautDTO implements ArgonautDTOContract
{
    use HasCasting;
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
        foreach ($this->prioritizedAttributes as $key) {
            if (array_key_exists($key, $attributes)) {
                $this->setAttribute($key, $attributes[$key]);
                unset($attributes[$key]);
            }
        }

        foreach ($attributes as $key => $value) {
            $this->setAttribute((string) $key, $value);
        }

        return $this;
    }

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

    /** @param array<string, mixed> $attributes */
    public function merge(array $attributes): static
    {
        return $this->setAttributes($attributes);
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
