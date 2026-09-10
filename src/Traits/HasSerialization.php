<?php

namespace YorCreative\ArgonautDTO\Traits;

use JsonException;
use RuntimeException;
use Traversable;
use WeakMap;
use YorCreative\ArgonautDTO\ArgonautDTOContract;
use YorCreative\ArgonautDTO\CircularReferenceException;
use YorCreative\ArgonautDTO\Collection;

trait HasSerialization
{
    /**
     * Serialization depth applied when no explicit depth is given.
     *
     * Circular references are caught precisely by the guard below, so this is
     * only a sanity bound on absurdly large structures. It is set far above any
     * realistic DTO tree and matches PHP's own default `json_encode()` depth.
     */
    public const DEFAULT_MAX_DEPTH = 512;

    /**
     * Instances currently being serialized, used to detect circular references.
     *
     * A WeakMap is deliberate: it never keeps a DTO alive, and because it holds
     * no instance property it can never leak into `get_object_vars()` output the
     * way a flag on the object would.
     *
     * @var WeakMap<object, true>|null
     */
    private static ?WeakMap $serializationGuard = null;

    /** @return list<string> */
    protected function getExcludedSerializationProperties(): array
    {
        return ['prioritizedAttributes', 'casts', 'nestedAssemblers', 'maps'];
    }

    protected function castOutputValue(mixed $value, int $depth): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof ArgonautDTOContract) {
            return $value->toArray($depth);
        }

        // Any traversable value is walked, not just this package's own
        // Collection: a DTO whose collectionClass() names another
        // implementation would otherwise emit it here as a raw object instead
        // of a nested array. DTOs are already handled above, so this only
        // reaches plain iterables.
        if ($value instanceof Collection || $value instanceof Traversable || is_array($value)) {
            $output = [];

            foreach ($value as $key => $item) {
                $output[$key] = $this->castOutputValue($item, $depth);
            }

            return $output;
        }

        return $value;
    }

    /**
     * @param  int|null  $depth  Maximum DTO nesting levels to serialize. Defaults to self::DEFAULT_MAX_DEPTH.
     * @return array<string, mixed>
     *
     * @throws CircularReferenceException when the graph references this instance again.
     * @throws RuntimeException when the depth limit is reached.
     */
    public function toArray(?int $depth = null): array
    {
        $remaining = $depth ?? self::DEFAULT_MAX_DEPTH;
        $guard = self::$serializationGuard ??= new WeakMap;

        if (isset($guard[$this])) {
            throw new CircularReferenceException(static::class);
        }

        if ($remaining <= 0) {
            throw new RuntimeException(
                static::class.' exceeded the maximum serialization depth. '
                .'Pass a larger $depth if the structure is genuinely this deep.'
            );
        }

        $excluded = $this->getExcludedSerializationProperties();
        $attributes = get_object_vars($this);
        $output = [];

        $guard[$this] = true;

        try {
            foreach ($attributes as $key => $value) {
                if (! in_array($key, $excluded, true)) {
                    $output[$key] = $this->castOutputValue($value, $remaining - 1);
                }
            }
        } finally {
            // Released even when serialization fails, so a later attempt on the
            // same instance is not mistaken for a circular reference.
            unset($guard[$this]);
        }

        return $output;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param  int|null  $depth  Maximum DTO nesting levels to serialize. Defaults to self::DEFAULT_MAX_DEPTH.
     *
     * @throws CircularReferenceException when the graph references this instance again.
     * @throws RuntimeException when the depth limit is reached or encoding fails.
     */
    public function toJson(int $options = 0, ?int $depth = null): string
    {
        return $this->encodeJson($this->toArray($depth), $options);
    }

    /**
     * Shared by toJson() and HasKeyMapping::toMappedJson() so both encode
     * with identical semantics and can never silently diverge.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws RuntimeException when encoding fails.
     */
    protected function encodeJson(array $data, int $options): string
    {
        try {
            try {
                return json_encode($data, $options | JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                if ($exception->getCode() !== JSON_ERROR_DEPTH) {
                    throw $exception;
                }

                // json_encode() applies its own 512 level limit, and every array
                // or collection of DTOs adds a level on top of the DTO nesting —
                // so a graph the walk depth explicitly permitted can still be
                // refused by the encoder. The walk is the limit the caller asked
                // for, so retry with exactly enough headroom for what it built.
                return json_encode($data, $options | JSON_THROW_ON_ERROR, self::measureDepth($data) + 1);
            }
        } catch (JsonException $exception) {
            throw new RuntimeException('JSON error: '.$exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return int<1, max>
     */
    private static function measureDepth(array $data): int
    {
        $depth = 1;

        foreach ($data as $value) {
            if (is_array($value)) {
                $depth = max($depth, 1 + self::measureDepth($value));
            }
        }

        return $depth;
    }

    /** @return array<string, mixed> */
    public function only(string ...$keys): array
    {
        return array_intersect_key($this->toArray(), array_flip($keys));
    }

    /** @return array<string, mixed> */
    public function except(string ...$keys): array
    {
        return array_diff_key($this->toArray(), array_flip($keys));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<static>
     */
    public static function collection(array $items = []): Collection
    {
        return (new Collection($items))->map(fn (mixed $item): static => new static($item));
    }
}
