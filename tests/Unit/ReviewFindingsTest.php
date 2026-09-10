<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\CollidingInverseMapDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ContainsMatcher;
use YorCreative\ArgonautDTO\Tests\Fixtures\CountingRulesDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\DuplicateMapFromDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\MappedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\MappedSetterDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NonPublicPropertyDTO;
use YorCreative\ArgonautDTO\ValidationException;

/**
 * Regression coverage for the silent-failure modes found reviewing 1.1.0.
 *
 * Every case here previously lost data, no-opped, or reported a misleading
 * value without raising anything. The shared decision for 1.1.0 is that a
 * misconfiguration fails loudly at the point it is detectable, rather than
 * producing output that looks plausible.
 */
class ReviewFindingsTest extends TestCase
{
    // -----------------------------------------------------------------
    // toMappedArray() inverse-key collision
    // -----------------------------------------------------------------

    public function test_mapped_output_raises_when_two_keys_collide(): void
    {
        // $maps renames fullName -> "name" on output, but "name" is also a real
        // property. Both land on the same output key; one value used to be
        // dropped with no signal.
        $dto = new CollidingInverseMapDTO(['fullName' => 'Ada']);
        $dto->name = 'kept';

        $this->assertSame(
            ['fullName' => 'Ada', 'name' => 'kept'],
            $dto->toArray(),
            'Unmapped output is unaffected.'
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/collide|collision/i');

        $dto->toMappedArray();
    }

    public function test_mapped_json_raises_on_the_same_collision(): void
    {
        $dto = new CollidingInverseMapDTO(['fullName' => 'Ada']);
        $dto->name = 'kept';

        $this->expectException(LogicException::class);

        $dto->toMappedJson();
    }

    public function test_mapped_output_is_unaffected_without_a_collision(): void
    {
        // MappedDTO renames onto keys that are not themselves properties, so
        // the inverse is unambiguous and nothing is dropped.
        $dto = new MappedDTO(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $this->assertSame(
            ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
            $dto->toMappedArray(),
            'A mapping with no competing property still round-trips.'
        );
    }

    // -----------------------------------------------------------------
    // setAttribute() honours key mapping
    // -----------------------------------------------------------------

    public function test_set_attribute_applies_key_mapping(): void
    {
        $viaConstructor = new MappedSetterDTO(['first_name' => 'Ada']);
        $viaSetter = (new MappedSetterDTO([]))->setAttribute('first_name', 'Ada');

        $this->assertSame('Ada', $viaConstructor->firstName);
        $this->assertSame(
            'Ada',
            $viaSetter->firstName,
            'setAttribute() must map incoming keys like every other input path.'
        );
    }

    public function test_set_attribute_still_accepts_the_property_name(): void
    {
        $dto = (new MappedSetterDTO([]))->setAttribute('firstName', 'Ada');

        $this->assertSame('Ada', $dto->firstName);
    }

    public function test_chained_maps_are_not_applied_twice(): void
    {
        // a -> b and b -> c. Mapping the array and then mapping each key again
        // would walk 'a' all the way to 'c'.
        $dto = new MappedSetterDTO(['a' => 'value']);

        $this->assertSame('value', $dto->b, 'A mapped key must not be re-mapped through a second hop.');
        $this->assertSame('', $dto->c);
    }

    // -----------------------------------------------------------------
    // Duplicate #[MapFrom]
    // -----------------------------------------------------------------

    public function test_duplicate_map_from_raises(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/MapFrom/');

        new DuplicateMapFromDTO(['key' => 'v']);
    }

    // -----------------------------------------------------------------
    // Collection::contains() with non-Closure callables
    // -----------------------------------------------------------------

    public function test_contains_accepts_a_non_closure_callable(): void
    {
        // Predicates are invoked as ($item, $key), so the callable must accept
        // both. This is the [$object, 'method'] form.
        $collection = new Collection([1, 2, 3]);
        $matcher = new ContainsMatcher(2);

        $this->assertTrue($collection->contains([$matcher, 'matches']));
        $this->assertFalse($collection->contains([new ContainsMatcher(99), 'matches']));
    }

    public function test_contains_accepts_an_invokable_object(): void
    {
        $predicate = new class
        {
            public function __invoke(mixed $item): bool
            {
                return $item === 2;
            }
        };

        $this->assertTrue((new Collection([1, 2, 3]))->contains($predicate));
    }

    public function test_contains_treats_strings_as_values_not_callables(): void
    {
        // 'is_int' is a callable string, but a collection of strings must be
        // searchable for it as a value. Strings are never predicates.
        $this->assertTrue((new Collection(['is_int', 'other']))->contains('is_int'));
        $this->assertFalse((new Collection([1, 2, 3]))->contains('is_int'));
    }

    // -----------------------------------------------------------------
    // Collection::pluck() against non-public properties
    // -----------------------------------------------------------------

    public function test_pluck_raises_for_an_inaccessible_property(): void
    {
        $collection = new Collection([new NonPublicPropertyDTO([])]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/not accessible|not public/i');

        $collection->pluck('secret');
    }

    public function test_pluck_still_returns_null_for_an_absent_key(): void
    {
        // An absent key is ordinary for a heterogeneous collection and stays
        // null. Only a property that exists but cannot be read is an error.
        $collection = new Collection([new NonPublicPropertyDTO([])]);

        $this->assertSame([null], $collection->pluck('nothingNamedThis')->all());
        $this->assertSame(['shown'], $collection->pluck('shown')->all());
    }

    // -----------------------------------------------------------------
    // validateAll() does not validate twice
    // -----------------------------------------------------------------

    public function test_validate_all_validates_each_item_once_when_throwing(): void
    {
        CountingRulesDTO::$ruleCalls = 0;

        $collection = new Collection([new CountingRulesDTO(['email' => 'not-an-email'])]);

        try {
            $collection->validateAll(true);
            $this->fail('validateAll(true) should have thrown.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->getErrors());
        }

        $this->assertSame(
            1,
            CountingRulesDTO::$ruleCalls,
            'The failing item must not be validated a second time to re-raise.'
        );
    }

    public function test_validate_all_still_reports_errors_without_throwing(): void
    {
        CountingRulesDTO::$ruleCalls = 0;

        $collection = new Collection([new CountingRulesDTO(['email' => 'not-an-email'])]);
        $errors = $collection->validateAll(false);

        $this->assertIsArray($errors);
        $this->assertArrayHasKey(0, $errors);
        $this->assertSame(1, CountingRulesDTO::$ruleCalls);
    }

    public function test_validate_all_passes_for_valid_items(): void
    {
        $collection = new Collection([new CountingRulesDTO(['email' => 'ada@example.com'])]);

        $this->assertTrue($collection->validateAll(true));
    }
}
