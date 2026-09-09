<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Attributes\CastWith;
use YorCreative\ArgonautDTO\CastsArgonautAttribute;
use YorCreative\ArgonautDTO\Tests\Fixtures\UppercaseCast;

final class CustomCastTest extends TestCase
{
    public function test_cast_with_compiles_to_the_cast_class_string(): void
    {
        self::assertSame(UppercaseCast::class, (new CastWith(UppercaseCast::class))->toCast());
    }

    public function test_a_cast_class_implements_the_contract(): void
    {
        self::assertInstanceOf(CastsArgonautAttribute::class, new UppercaseCast);
    }

    public function test_a_cast_transforms_the_value_it_is_given(): void
    {
        self::assertSame('ADA', (new UppercaseCast)->get('name', 'ada'));
    }

    public function test_a_cast_receives_the_property_key(): void
    {
        self::assertSame('name:ADA', (new UppercaseCast)->get('name', 'ada', true));
    }
}
