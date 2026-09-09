<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Attributes\CastWith;
use YorCreative\ArgonautDTO\CastsArgonautAttribute;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\AssembledCustomCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\AttributedArrayCustomCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\AttributedCustomCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ConflictingCustomCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\CountingCast;
use YorCreative\ArgonautDTO\Tests\Fixtures\CountingCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\CustomCastDTO;
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

    public function test_a_casts_array_entry_applies_a_custom_cast(): void
    {
        self::assertSame('ADA', (new CustomCastDTO(['name' => 'ada']))->name);
    }

    public function test_the_cast_with_attribute_applies_a_custom_cast(): void
    {
        self::assertSame('ADA', (new AttributedCustomCastDTO(['name' => 'ada']))->name);
    }

    public function test_the_casts_array_wins_over_a_cast_with_attribute(): void
    {
        self::assertSame('ada', (new ConflictingCustomCastDTO(['name' => 'ada']))->name);
    }

    public function test_a_custom_cast_is_not_constructed_as_a_dto(): void
    {
        // Guards Failure 1: without the ordering guard, class_exists() would
        // reach castToSingleModel() and do new UppercaseCast('ada').
        self::assertIsString((new CustomCastDTO(['name' => 'ada']))->name);
    }

    public function test_null_never_reaches_a_custom_cast(): void
    {
        self::assertNull((new CustomCastDTO(['name' => null]))->name);
    }

    public function test_the_cast_class_is_instantiated_only_once(): void
    {
        // Proves the shared-instance contract the interface docblock promises,
        // by counting constructions rather than reaching into library internals.
        CountingCast::$instantiations = 0;

        new CountingCastDTO(['a' => 'x', 'b' => 'y']);
        new CountingCastDTO(['a' => 'p', 'b' => 'q']);

        // Four cast applications across two DTOs, one construction.
        self::assertSame(1, CountingCast::$instantiations);
    }

    public function test_a_string_valued_custom_cast_applies_on_an_assembler_property(): void
    {
        self::assertSame('ADA', (new AssembledCustomCastDTO(['single' => 'ada']))->single);
    }

    public function test_an_array_valued_custom_cast_applies_on_an_assembler_property(): void
    {
        // Guards Failure 3: without your array-custom-cast branch this reaches
        // castToArrayOfModels() and constructs UppercaseCast instances.
        self::assertSame(['ADA', 'GRACE'], (new AssembledCustomCastDTO(['many' => ['ada', 'grace']]))->many);
    }

    public function test_a_string_valued_custom_cast_is_not_sent_to_the_assembler(): void
    {
        // Payload MUST be an array: assembleNestedValue() only calls the
        // assembler for array/object values, so a scalar never reaches
        // castTarget()'s guard and the test would pass with the guard removed.
        self::assertSame(['x' => 'ada'], (new AssembledCustomCastDTO(['single' => ['x' => 'ada']]))->single);
    }

    public function test_an_array_valued_custom_cast_is_not_sent_to_the_assembler(): void
    {
        self::assertSame([['x' => 'ada']], (new AssembledCustomCastDTO(['many' => [['x' => 'ada']]]))->many);
    }

    public function test_a_collection_valued_custom_cast_applies_the_cast(): void
    {
        $dto = new AssembledCustomCastDTO(['collection' => ['ada', 'grace']]);

        self::assertInstanceOf(Collection::class, $dto->collection);
        self::assertSame(['ADA', 'GRACE'], $dto->collection->all());
    }

    public function test_a_collection_valued_custom_cast_is_not_sent_to_the_assembler(): void
    {
        // Array payload required, same reason as the other assembler guards.
        $dto = new AssembledCustomCastDTO(['collection' => [['x' => 'ada']]]);

        self::assertSame([['x' => 'ada']], $dto->collection->all());
    }

    public function test_the_cast_with_attribute_supports_the_array_form(): void
    {
        $dto = new AttributedArrayCustomCastDTO(['tags' => ['ada', 'grace']]);

        self::assertSame(['ADA', 'GRACE'], $dto->tags);
    }
}
