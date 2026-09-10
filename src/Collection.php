<?php

namespace YorCreative\ArgonautDTO;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use LogicException;
use ReflectionProperty;
use Traversable;

/**
 * A deliberately small, dependency-free collection used by Argonaut.
 *
 * It provides the operations DTO consumers normally need without requiring
 * Illuminate or another collection framework.
 *
 * @template TValue
 *
 * @implements ArrayAccess<int|string, TValue>
 * @implements IteratorAggregate<int|string, TValue>
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<int|string, TValue> */
    protected array $items;

    /** @param iterable<int|string, TValue> $items */
    public function __construct(iterable $items = [])
    {
        $this->items = is_array($items) ? $items : iterator_to_array($items);
    }

    /** @return array<int|string, TValue> */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @template TMapped
     *
     * @param  callable(TValue, int|string): TMapped  $callback
     * @return static<TMapped>
     */
    public function map(callable $callback): static
    {
        $mapped = [];

        foreach ($this->items as $key => $item) {
            $mapped[$key] = $callback($item, $key);
        }

        return new static($mapped);
    }

    /**
     * @param  (callable(TValue, int|string): bool)|null  $callback
     */
    public function filter(?callable $callback = null): static
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @template TDefault
     *
     * @param  TDefault  $default
     * @return TValue|TDefault
     */
    public function first(mixed $default = null): mixed
    {
        foreach ($this->items as $item) {
            return $item;
        }

        return $default;
    }

    /**
     * @template TReduce
     *
     * @param  callable(TReduce, TValue, int|string): TReduce  $callback
     * @param  TReduce  $initial
     * @return TReduce
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $accumulator = $initial;

        foreach ($this->items as $key => $item) {
            $accumulator = $callback($accumulator, $item, $key);
        }

        return $accumulator;
    }

    /**
     * @template TDefault
     *
     * @param  TDefault  $default
     * @return TValue|TDefault
     */
    public function last(mixed $default = null): mixed
    {
        if ($this->items === []) {
            return $default;
        }

        return $this->items[array_key_last($this->items)];
    }

    /**
     * Accepts either a value compared strictly, or a predicate.
     *
     * @param  TValue|callable(TValue, int|string): bool  $value
     */
    public function contains(mixed $value): bool
    {
        // A string is always a value, never a predicate: a collection of
        // strings has to stay searchable for one that happens to name a
        // function. Every other callable form -- Closure, [$object, 'method'],
        // an __invoke object -- is invoked as the documented predicate.
        if (is_string($value) || ! is_callable($value)) {
            return in_array($value, $this->items, true);
        }

        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string  $value  key or property to extract from each item
     * @param  string|null  $key  key or property to index the result by
     * @return static<mixed>
     */
    public function pluck(string $value, ?string $key = null): static
    {
        $plucked = [];

        foreach ($this->items as $item) {
            $extracted = $this->extract($item, $value);

            if ($key === null) {
                $plucked[] = $extracted;

                continue;
            }

            /** @var int|string $index */
            $index = $this->extract($item, $key);
            $plucked[$index] = $extracted;
        }

        return new static($plucked);
    }

    /**
     * @param  callable(TValue, int|string): (int|string)  $callback
     * @return static<static<TValue>>
     */
    public function groupBy(callable $callback): static
    {
        $groups = [];

        foreach ($this->items as $key => $item) {
            $groups[$callback($item, $key)][] = $item;
        }

        $collections = [];

        foreach ($groups as $group => $items) {
            $collections[$group] = new static($items);
        }

        return new static($collections);
    }

    /**
     * @param  callable(TValue, int|string): (int|string)  $callback
     */
    public function keyBy(callable $callback): static
    {
        $keyed = [];

        foreach ($this->items as $key => $item) {
            $keyed[$callback($item, $key)] = $item;
        }

        return new static($keyed);
    }

    private function extract(mixed $item, string $key): mixed
    {
        if (is_array($item)) {
            return $item[$key] ?? null;
        }

        if ($item instanceof ArrayAccess) {
            return $item->offsetExists($key) ? $item[$key] : null;
        }

        if (is_object($item)) {
            // ?? reports false for a property that exists but is not readable
            // here, which is indistinguishable from a legitimately null value
            // and from a typo. A declared-but-inaccessible property is a
            // programming error and is reported as one; a key that simply is
            // not there stays null, as it does for arrays above.
            if (property_exists($item, $key) && ! (new ReflectionProperty($item, $key))->isPublic()) {
                throw new LogicException(sprintf(
                    'Collection::pluck() cannot read $%s on %s: the property is not accessible. '
                    .'Expose it, or pluck a public property.',
                    $key,
                    $item::class,
                ));
            }

            return $item->{$key} ?? null;
        }

        return null;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    /** @return Traversable<int|string, TValue> */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /** @return TValue|null */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /** @param TValue $value */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;

            return;
        }

        $this->items[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /** @return array<int|string, mixed> */
    public function jsonSerialize(): array
    {
        return array_map(static function (mixed $value): mixed {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }

            return $value;
        }, $this->items);
    }

    /**
     * Validate every item, reporting errors keyed by the item's collection key.
     *
     * A non-DTO item, or a DTO whose class declares no rules(), is a
     * programming error and surfaces as a LogicException rather than being
     * reported as invalid.
     *
     * @return true|array<int|string, array<string, list<string>>>
     *
     * @throws ValidationException when $throw is true and any item is invalid.
     */
    public function validateAll(bool $throw = true): bool|array
    {
        $errors = [];
        $firstFailure = null;

        foreach ($this->items as $key => $item) {
            if (! $item instanceof ArgonautDTOContract) {
                throw new LogicException(sprintf(
                    'Collection::validateAll() requires every item to implement %s; %s given at key %s.',
                    ArgonautDTOContract::class,
                    get_debug_type($item),
                    var_export($key, true),
                ));
            }

            $result = $item->validate(false);

            if ($result !== true) {
                /** @var array<string, list<string>> $result */
                $errors[$key] = $result;
                $firstFailure ??= $item;
            }
        }

        if ($errors === []) {
            return true;
        }

        if ($throw) {
            // Built from the errors already captured above. Re-running
            // validate(true) on the failing item would validate it a second
            // time, doubling any work or side effect in rules().
            throw new ValidationException(reset($errors));
        }

        return $errors;
    }

    /**
     * Report whether every item satisfies its own rules().
     *
     * Only validation failure is reported as false. A non-DTO item or a
     * missing rules() method is a programming error and is allowed to surface.
     */
    public function isValidAll(): bool
    {
        return $this->validateAll(false) === true;
    }
}
