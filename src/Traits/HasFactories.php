<?php

namespace YorCreative\ArgonautDTO\Traits;

/**
 * Named constructors shared by ArgonautDTO and ArgonautImmutableDTO.
 *
 * The two base classes are siblings rather than parent and child, so this
 * trait is how they share factories. Both constructors accept an attribute
 * array, so new static() dispatches correctly for either.
 */
trait HasFactories
{
    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): static
    {
        return new static($attributes);
    }

    /**
     * @throws \JsonException when $json is not a valid JSON object
     */
    public static function fromJson(string $json): static
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new static($decoded);
    }
}
