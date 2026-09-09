<?php

namespace YorCreative\ArgonautDTO\Traits;

use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use YorCreative\ArgonautDTO\Attributes\MapFrom;

trait HasKeyMapping
{
    /** @var array<string, string> incoming key => property name */
    protected array $maps = [];

    /**
     * Per-class #[MapFrom] derived maps. Memoized for the same reason
     * HasCasting::$attributeCastMap is: reflecting every property on every
     * hydration would dominate cost in a loop.
     *
     * @var array<class-string, array<string, string>>
     */
    protected static array $attributeMapMap = [];

    /**
     * @return array<string, string>
     */
    private function attributeMaps(): array
    {
        $class = static::class;

        if (! isset(static::$attributeMapMap[$class])) {
            static::$attributeMapMap[$class] = $this->discoverAttributeMaps();
        }

        return static::$attributeMapMap[$class];
    }

    /**
     * @return array<string, string>
     */
    private function discoverAttributeMaps(): array
    {
        $maps = [];

        foreach ((new ReflectionClass($this))->getProperties() as $property) {
            $attributes = $property->getAttributes(MapFrom::class, ReflectionAttribute::IS_INSTANCEOF);

            if ($attributes === []) {
                continue;
            }

            $maps[$attributes[0]->newInstance()->from] = $property->getName();
        }

        return $maps;
    }

    /**
     * The effective incoming key => property name map. $maps is an instance
     * property a consumer may mutate at runtime, and it wins over the
     * attribute-derived map, mirroring the $casts precedence. The precedence
     * is per target property, not per incoming key: if $maps declares any
     * incoming key that maps to a given property, every attribute-derived
     * mapping targeting that same property is dropped, even when the
     * competing incoming keys differ.
     *
     * @return array<string, string>
     */
    protected function keyMaps(): array
    {
        $mappedProperties = array_values($this->maps);

        $attributeMaps = array_filter(
            $this->attributeMaps(),
            fn (string $property): bool => ! in_array($property, $mappedProperties, true)
        );

        return array_merge($attributeMaps, $this->maps);
    }

    /**
     * Rebuild $attributes in input order, renaming any key found in
     * keyMaps() to its target property name. Rebuilding in input order keeps
     * collisions deterministic: when both a mapped key and its target
     * property appear in the same input, the one occurring later wins.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function mapInputKeys(array $attributes): array
    {
        $maps = $this->keyMaps();

        if ($maps === []) {
            return $attributes;
        }

        $mapped = [];

        foreach ($attributes as $key => $value) {
            $mapped[$maps[$key] ?? $key] = $value;
        }

        return $mapped;
    }

    /**
     * The top-level output of toArray($depth), with each key renamed through
     * the inverse of keyMaps() (property name => incoming key). Keys with no
     * mapping keep their own name.
     *
     * This is top-level only: by the time toArray() runs, nested DTOs have
     * already been flattened into plain arrays, so there is no nested $maps
     * left to apply. Rewalking the object graph to work around that is out of
     * scope by design.
     *
     * @return array<string, mixed>
     */
    public function toMappedArray(?int $depth = null): array
    {
        $inverse = array_flip($this->keyMaps());

        $mapped = [];

        foreach ($this->toArray($depth) as $key => $value) {
            $mapped[$inverse[$key] ?? $key] = $value;
        }

        return $mapped;
    }

    /**
     * toMappedArray(), JSON-encoded through the same encodeJson() helper
     * HasSerialization::toJson() uses, so both encode with identical
     * semantics (JSON_THROW_ON_ERROR, the depth-retry, and the
     * RuntimeException wrapping).
     *
     * @throws RuntimeException when encoding fails.
     */
    public function toMappedJson(int $options = 0, ?int $depth = null): string
    {
        return $this->encodeJson($this->toMappedArray($depth), $options);
    }
}
