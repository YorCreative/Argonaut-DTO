<?php

namespace YorCreative\ArgonautDTO\Traits;

use LogicException;
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

            $from = $attributes[0]->newInstance()->from;

            // Two properties claiming the same incoming key is ambiguous: only
            // one can receive the value, and which one depends on reflection
            // order. Reject it rather than silently leaving a property unset.
            if (isset($maps[$from])) {
                throw new LogicException(sprintf(
                    '%s declares #[MapFrom(%s)] on both $%s and $%s. An incoming key may map to only one property.',
                    static::class,
                    var_export($from, true),
                    $maps[$from],
                    $property->getName(),
                ));
            }

            $maps[$from] = $property->getName();
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

        // Not array_merge(): it renumbers integer keys, and PHP stores a
        // numeric-string array key as an integer, so an incoming alias like
        // '123' would be reindexed to 0 and stop matching. Assigning key by
        // key preserves it while keeping $maps the winner on conflict.
        foreach ($this->maps as $incoming => $property) {
            $attributeMaps[$incoming] = $property;
        }

        return $attributeMaps;
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
            $target = $inverse[$key] ?? $key;

            // Renaming can land two distinct properties on one output key --
            // when a property is mapped onto the name of another property that
            // also serializes. Writing both would drop one value silently, so
            // the ambiguous mapping is reported instead.
            if (array_key_exists($target, $mapped)) {
                throw new LogicException(sprintf(
                    '%s::toMappedArray() cannot rename %s to %s: two keys collide on %s. '
                    .'Rename the mapping, or exclude the competing property from serialization.',
                    static::class,
                    var_export($key, true),
                    var_export($target, true),
                    var_export($target, true),
                ));
            }

            $mapped[$target] = $value;
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
