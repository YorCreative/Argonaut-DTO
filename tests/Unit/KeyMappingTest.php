<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Tests\Fixtures\AttributedMappedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ConflictingMappedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ImmutableMappedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\MappedCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\MappedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\PrioritizedMappedDTO;

final class KeyMappingTest extends TestCase
{
    public function test_the_maps_array_renames_incoming_keys(): void
    {
        $dto = new MappedDTO(['first_name' => 'Jane', 'last_name' => 'Doe']);

        self::assertSame('Jane', $dto->firstName);
        self::assertSame('Doe', $dto->lastName);
    }

    public function test_unmapped_keys_pass_through_untouched(): void
    {
        self::assertSame('Jane', (new MappedDTO(['firstName' => 'Jane']))->firstName);
    }

    public function test_the_map_from_attribute_renames_incoming_keys(): void
    {
        self::assertSame('Jane', (new AttributedMappedDTO(['first_name' => 'Jane']))->firstName);
    }

    public function test_the_maps_array_wins_over_the_attribute(): void
    {
        $dto = new ConflictingMappedDTO(['array_key' => 'from-array', 'attr_key' => 'from-attr']);

        self::assertSame('from-array', $dto->value);
    }

    public function test_a_later_key_wins_a_collision(): void
    {
        // Both the mapped key and its target property are present. The later
        // one wins. with() on the immutable class depends on this.
        $dto = new MappedDTO(['firstName' => 'Property', 'first_name' => 'Mapped']);

        self::assertSame('Mapped', $dto->firstName);
    }

    public function test_mapping_applies_to_immutable_dtos(): void
    {
        self::assertSame('Jane', (new ImmutableMappedDTO(['first_name' => 'Jane']))->firstName);
    }

    public function test_with_accepts_a_mapped_key(): void
    {
        $dto = new MappedDTO(['first_name' => 'Jane', 'last_name' => 'Doe']);

        self::assertSame('Smith', $dto->with(['last_name' => 'Smith'])->lastName);
        self::assertSame('Jane', $dto->with(['last_name' => 'Smith'])->firstName);
    }

    public function test_the_maps_table_does_not_serialize(): void
    {
        self::assertSame(['firstName', 'lastName'], array_keys((new MappedDTO(['first_name' => 'Jane']))->toArray()));
    }

    public function test_mapping_runs_before_the_prioritized_pass(): void
    {
        // Input order is deliberately reversed relative to $prioritizedAttributes
        // (['firstName', 'lastName']). If mapInputKeys() ran AFTER the
        // prioritized loop, 'first_name' and 'last_name' would not match
        // $prioritizedAttributes at all, so both keys would fall through to the
        // remaining-attributes pass and be applied in this (reversed) input
        // order instead of the canonical one: setLastName() would then run
        // before setFirstName() ever sets firstName, deriving fullName from an
        // empty firstName.
        $dto = new PrioritizedMappedDTO(['last_name' => 'Doe', 'first_name' => 'Jane']);

        self::assertSame('Jane', $dto->firstName);
        self::assertSame('Jane Doe', $dto->fullName);
    }

    public function test_to_mapped_array_emits_the_incoming_key_names(): void
    {
        $dto = new MappedDTO(['first_name' => 'Jane', 'last_name' => 'Doe']);

        self::assertSame(['first_name' => 'Jane', 'last_name' => 'Doe'], $dto->toMappedArray());
    }

    public function test_to_array_is_unchanged_by_mapping(): void
    {
        $dto = new MappedDTO(['first_name' => 'Jane', 'last_name' => 'Doe']);

        self::assertSame(['firstName' => 'Jane', 'lastName' => 'Doe'], $dto->toArray());
    }

    public function test_unmapped_properties_keep_their_own_names(): void
    {
        $dto = new AttributedMappedDTO(['first_name' => 'Jane']);

        self::assertSame(['first_name' => 'Jane'], $dto->toMappedArray());
    }

    public function test_to_mapped_json_emits_the_incoming_key_names(): void
    {
        $dto = new MappedDTO(['first_name' => 'Jane', 'last_name' => 'Doe']);

        self::assertSame('{"first_name":"Jane","last_name":"Doe"}', $dto->toMappedJson());
    }

    public function test_mapped_output_works_on_immutable_dtos(): void
    {
        self::assertSame(['first_name' => 'Jane'], (new ImmutableMappedDTO(['first_name' => 'Jane']))->toMappedArray());
    }

    public function test_with_accepts_a_mapped_key_on_an_immutable_dto(): void
    {
        // Explicitly load-bearing: the immutable with() merges changes AFTER
        // raw property-keyed state, so a mapped change can only win because
        // mapInputKeys() rebuilds in input order and the later key wins.
        $dto = new ImmutableMappedDTO(['first_name' => 'Jane']);

        self::assertSame('Grace', $dto->with(['first_name' => 'Grace'])->firstName);
    }

    public function test_a_mapped_property_is_still_cast(): void
    {
        // The cast must be looked up by the POST-mapping property name.
        $dto = new MappedCastDTO(['first_name' => 123]);

        self::assertSame('123', $dto->firstName);
    }

    public function test_to_mapped_json_reports_encoding_failures_like_to_json(): void
    {
        $dto = new MappedDTO(['first_name' => "bad\xB1utf"]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON error: Malformed UTF-8 characters');

        $dto->toMappedJson();
    }
}
