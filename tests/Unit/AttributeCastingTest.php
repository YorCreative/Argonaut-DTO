<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Attributes\CastCollection;
use YorCreative\ArgonautDTO\Attributes\CastEnum;
use YorCreative\ArgonautDTO\Attributes\CastTo;
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
}
