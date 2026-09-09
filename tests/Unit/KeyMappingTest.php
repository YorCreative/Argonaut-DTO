<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Tests\Fixtures\AttributedMappedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ConflictingMappedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ImmutableMappedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\MappedDTO;

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
}
