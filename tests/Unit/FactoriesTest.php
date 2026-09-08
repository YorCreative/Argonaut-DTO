<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use JsonException;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\ArgonautImmutableDTO;
use YorCreative\ArgonautDTO\Attributes\CastTo;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\ProfileDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\TagDTO;

final class FactoriesTest extends TestCase
{
    public function test_from_array_builds_the_called_class(): void
    {
        $dto = ProfileDTO::fromArray(['fullName' => 'Ada']);

        self::assertInstanceOf(ProfileDTO::class, $dto);
        self::assertSame('Ada', $dto->fullName);
    }

    public function test_from_array_applies_casting(): void
    {
        self::assertSame('123', ProfileDTO::fromArray(['fullName' => 123])->fullName);
    }

    public function test_from_json_decodes_and_builds(): void
    {
        self::assertSame('Ada', ProfileDTO::fromJson('{"fullName":"Ada"}')->fullName);
    }

    public function test_from_json_throws_on_malformed_input(): void
    {
        $this->expectException(JsonException::class);

        ProfileDTO::fromJson('{not json');
    }

    public function test_the_factories_work_on_immutable_dtos(): void
    {
        $dto = ImmutableFactoryDTO::fromArray(['label' => 'x']);

        self::assertSame('x', $dto->label);
        self::assertSame('y', ImmutableFactoryDTO::fromJson('{"label":"y"}')->label);
    }

    public function test_the_pre_existing_collection_factory_is_unchanged(): void
    {
        $collection = TagDTO::collection([['name' => 'a'], ['name' => 'b']]);

        self::assertInstanceOf(Collection::class, $collection);
        self::assertCount(2, $collection);
        self::assertInstanceOf(TagDTO::class, $collection->first());
        self::assertSame('a', $collection->first()->name);
    }

    public function test_cast_attributes_work_on_immutable_dtos(): void
    {
        $dto = ImmutableAttributedDTO::fromArray(['tag' => ['name' => 'z']]);

        self::assertInstanceOf(TagDTO::class, $dto->tag);
        self::assertSame('z', $dto->tag->name);
    }

    public function test_from_json_rejects_valid_json_that_is_not_an_object(): void
    {
        $this->expectException(JsonException::class);

        ProfileDTO::fromJson('null');
    }

    public function test_from_json_rejects_a_bare_json_scalar(): void
    {
        $this->expectException(JsonException::class);

        ProfileDTO::fromJson('"just a string"');
    }
}

final class ImmutableFactoryDTO extends ArgonautImmutableDTO
{
    public readonly string $label;
}

final class ImmutableAttributedDTO extends ArgonautImmutableDTO
{
    #[CastTo(TagDTO::class)]
    public readonly TagDTO $tag;
}
