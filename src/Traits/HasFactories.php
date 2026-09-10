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
     * @throws \JsonException when $json is not valid JSON, or does not decode to an object
     */
    public static function fromJson(string $json): static
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        // The root type is read from the document, not inferred from the
        // decoded shape. Associative decoding erases the difference: a JSON
        // object with numeric keys -- {"0":"a"} -- decodes to a PHP list and
        // array_is_list() rejected it, while an empty JSON array [] was
        // indistinguishable from {} and slipped through.
        if (! is_array($decoded) || ltrim($json)[0] !== '{') {
            throw new \JsonException(sprintf(
                '%s::fromJson() expects a JSON object, %s given.',
                static::class,
                $decoded === null ? 'null' : get_debug_type($decoded),
            ));
        }

        /** @var array<string, mixed> $decoded */
        return new static($decoded);
    }
}
