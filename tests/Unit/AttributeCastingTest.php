<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Attributes\CastCollection;
use YorCreative\ArgonautDTO\Attributes\CastEnum;
use YorCreative\ArgonautDTO\Attributes\CastTo;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\AttributedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\AttributedParentDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ConflictingCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\OverridingChildDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ProfileDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\Status;
use YorCreative\ArgonautDTO\Tests\Fixtures\TagDTO;

final class AttributeCastingTest extends TestCase
{
    public function test_cast_to_compiles_to_a_class_string(): void
    {
        self::assertSame(TagDTO::class, (new CastTo(TagDTO::class))->toCast());
    }

    public function test_cast_to_many_compiles_to_a_wrapped_class_string(): void
    {
        self::assertSame([TagDTO::class], (new CastTo(TagDTO::class, many: true))->toCast());
    }

    public function test_cast_collection_compiles_to_a_prefixed_string(): void
    {
        self::assertSame(
            'collection:'.TagDTO::class,
            (new CastCollection(TagDTO::class))->toCast()
        );
    }

    public function test_cast_enum_compiles_to_the_enum_class_string(): void
    {
        self::assertSame(Status::class, (new CastEnum(Status::class))->toCast());
    }

    public function test_cast_to_hydrates_a_single_nested_dto(): void
    {
        $dto = new AttributedDTO(['primaryTag' => ['name' => 'alpha']]);

        self::assertInstanceOf(TagDTO::class, $dto->primaryTag);
    }

    public function test_cast_to_many_hydrates_an_array_of_dtos(): void
    {
        $dto = new AttributedDTO(['tags' => [['name' => 'a'], ['name' => 'b']]]);

        self::assertCount(2, $dto->tags);
        self::assertContainsOnlyInstancesOf(TagDTO::class, $dto->tags);
    }

    public function test_cast_collection_hydrates_a_collection_of_dtos(): void
    {
        $dto = new AttributedDTO(['tagCollection' => [['name' => 'a'], ['name' => 'b']]]);

        self::assertInstanceOf(Collection::class, $dto->tagCollection);
        self::assertCount(2, $dto->tagCollection);
        self::assertInstanceOf(TagDTO::class, $dto->tagCollection->first());
    }

    public function test_cast_enum_hydrates_a_backed_enum(): void
    {
        $dto = new AttributedDTO(['status' => 'active']);

        self::assertSame(Status::Active, $dto->status);
    }

    public function test_cast_enum_passes_through_an_existing_enum_instance(): void
    {
        self::assertSame(Status::Archived, (new AttributedDTO(['status' => Status::Archived]))->status);
    }

    public function test_the_casts_array_wins_over_an_attribute_on_the_same_property(): void
    {
        $dto = new ConflictingCastDTO(['value' => 42]);

        self::assertSame('42', $dto->value);
    }

    public function test_a_subclass_can_override_an_inherited_attribute_via_casts(): void
    {
        self::assertInstanceOf(TagDTO::class, (new AttributedParentDTO(['thing' => ['name' => 'x']]))->thing);
        self::assertSame('7', (new OverridingChildDTO(['thing' => 7]))->thing);
    }

    public function test_a_dto_with_no_attributes_is_unaffected(): void
    {
        $dto = new ProfileDTO(['fullName' => 123]);

        self::assertSame('123', $dto->fullName);
    }
}
