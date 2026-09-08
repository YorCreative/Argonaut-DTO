<?php

namespace YorCreative\ArgonautDTO\Traits;

use DateTimeInterface;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use Traversable;
use YorCreative\ArgonautDTO\ArgonautDTOContract;
use YorCreative\ArgonautDTO\Attributes\CastAttribute;
use YorCreative\ArgonautDTO\Collection;

trait HasCasting
{
    /** @var array<string, string|array<int, string>> */
    protected array $casts = [];

    /** @var array<string, class-string> */
    protected array $nestedAssemblers = [];

    /**
     * Per-class attribute-derived casts. Memoized for the same reason
     * ArgonautDTO::$setterMap is: reflecting every property on every
     * hydration would dominate cost in a loop.
     *
     * @var array<class-string, array<string, string|array<int, string>>>
     */
    protected static array $attributeCastMap = [];

    protected function castInputValue(string $key, mixed $value): mixed
    {
        // $casts is an instance property a consumer may mutate at runtime, so it
        // is looked up first and never folded into the static per-class cache.
        $cast = $this->casts[$key] ?? $this->attributeCasts()[$key] ?? null;
        $hasNestedAssembler = isset($this->nestedAssemblers[$key]);

        if ($hasNestedAssembler && $cast !== null) {
            $value = $this->assembleNestedValue($key, $cast, $value);
        }

        return $this->applyCast($cast, $value);
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function attributeCasts(): array
    {
        $class = static::class;

        if (! isset(static::$attributeCastMap[$class])) {
            static::$attributeCastMap[$class] = $this->discoverAttributeCasts();
        }

        return static::$attributeCastMap[$class];
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function discoverAttributeCasts(): array
    {
        $casts = [];

        foreach ((new ReflectionClass($this))->getProperties() as $property) {
            $attributes = $property->getAttributes(CastAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

            if ($attributes === []) {
                continue;
            }

            $casts[$property->getName()] = $attributes[0]->newInstance()->toCast();
        }

        return $casts;
    }

    /** @param string|array<int, mixed> $cast */
    private function assembleNestedValue(string $key, string|array $cast, mixed $value): mixed
    {
        $assemblerClass = $this->nestedAssemblers[$key];
        [$targetClass, $multiple] = $this->castTarget($cast);

        if ($targetClass === null) {
            return $value;
        }

        if ($multiple) {
            $items = $this->normalizeIterableValue($value, 'array');

            return array_map(
                fn (mixed $item): mixed => (is_array($item) || is_object($item))
                    ? $assemblerClass::assemble($item, $targetClass)
                    : $item,
                $items,
            );
        }

        if (is_array($value) || is_object($value)) {
            return $assemblerClass::assemble($value, $targetClass);
        }

        return $value;
    }

    /** @param string|array<int, mixed>|null $cast */
    private function applyCast(string|array|null $cast, mixed $value): mixed
    {
        if ($cast === null || $value === null) {
            return $value;
        }

        if (is_array($cast) && isset($cast[0]) && is_string($cast[0])) {
            return $this->castToArrayOfModels($cast[0], $value);
        }

        if (! is_string($cast)) {
            return $value;
        }

        if ($this->isScalarCast($cast)) {
            return $this->castScalar($cast, $value);
        }

        if (enum_exists($cast) && is_subclass_of($cast, \BackedEnum::class)) {
            return $this->castToEnum($cast, $value);
        }

        if (str_starts_with($cast, Collection::class.':') || str_starts_with($cast, 'collection:')) {
            return $this->castToCollectionModel($cast, $value);
        }

        if (class_exists($cast)) {
            return $this->castToSingleModel($cast, $value);
        }

        return $value;
    }

    /**
     * @param  string|array<int, mixed>  $cast
     * @return array{0: string|null, 1: bool}
     */
    private function castTarget(string|array $cast): array
    {
        if (is_array($cast) && isset($cast[0]) && is_string($cast[0])) {
            return [$cast[0], true];
        }

        if (is_string($cast) && (str_starts_with($cast, Collection::class.':') || str_starts_with($cast, 'collection:'))) {
            return [explode(':', $cast, 2)[1], true];
        }

        return [is_string($cast) && class_exists($cast) ? $cast : null, false];
    }

    private function isScalarCast(string $cast): bool
    {
        return in_array($cast, ['string', 'int', 'integer', 'float', 'bool', 'boolean'], true);
    }

    private function castScalar(string $cast, mixed $value): mixed
    {
        return match ($cast) {
            'string' => (string) $value,
            'int', 'integer' => (int) $value,
            'float' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            default => $value,
        };
    }

    protected function castToCollectionModel(string $cast, mixed $value): Collection
    {
        [, $class] = explode(':', $cast, 2);
        $items = $this->normalizeIterableValue($value, 'collection');

        return (new Collection($items))->map(fn (mixed $item): mixed => $this->castItem($class, $item));
    }

    /** @return array<int|string, mixed> */
    protected function castToArrayOfModels(string $class, mixed $value): array
    {
        $items = $this->normalizeIterableValue($value, 'array');

        return array_map(fn (mixed $item): mixed => $this->castItem($class, $item), $items);
    }

    private function castItem(string $class, mixed $item): mixed
    {
        if ($item instanceof $class) {
            return $item;
        }

        if (enum_exists($class) && is_subclass_of($class, \BackedEnum::class)) {
            return $class::from($item);
        }

        if ($item instanceof Collection) {
            $item = $item->all();
        } elseif ($item instanceof Traversable) {
            $item = iterator_to_array($item);
        } elseif (is_object($item)) {
            $item = get_object_vars($item);
        }

        $this->guardDTOAttributes($class, $item);

        return new $class($item);
    }

    protected function castToSingleModel(string $class, mixed $value): mixed
    {
        if (is_a($class, DateTimeInterface::class, true)) {
            return $value instanceof DateTimeInterface ? $value : new $class($value);
        }

        if ($value instanceof $class) {
            return $value;
        }

        if ($value instanceof Collection) {
            $value = $value->all();
        } elseif (is_object($value)) {
            $value = get_object_vars($value);
        }

        $this->guardDTOAttributes($class, $value);

        return new $class($value);
    }

    /**
     * Argonaut DTOs are constructed from an attribute array. Anything else
     * would surface as a TypeError from deep inside the constructor, so it is
     * reported here instead. Plain value objects are left alone: their
     * constructors legitimately accept scalars.
     */
    private function guardDTOAttributes(string $class, mixed $value): void
    {
        if (is_array($value) || ! is_a($class, ArgonautDTOContract::class, true)) {
            return;
        }

        throw new InvalidArgumentException(
            get_debug_type($value)." cannot be cast to {$class}; "
            ."an array of attributes, an object, or an existing {$class} is required."
        );
    }

    /** @param class-string<\BackedEnum> $enumClass */
    protected function castToEnum(string $enumClass, mixed $value): \BackedEnum
    {
        return $value instanceof $enumClass ? $value : $enumClass::from($value);
    }

    /** @return array<int|string, mixed> */
    protected function normalizeIterableValue(mixed $value, string $target): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Collection) {
            return $value->all();
        }

        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        throw new InvalidArgumentException(get_debug_type($value)." must be iterable to cast to {$target}.");
    }
}
