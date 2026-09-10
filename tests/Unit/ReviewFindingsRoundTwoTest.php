<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use JsonException;
use LogicException;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\BulkNullCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\BulkNullCollectionCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\MagicAccessorDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NonPublicPropertyDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NumericMapDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\PlainFactoryDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\PrivateStateImmutableDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\RuntimeCastsImmutableDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\UppercasingSetterDTO;

/**
 * Second review pass over the 1.1.0 surface.
 *
 * Two of these are regressions from the first pass: routing bulk assignment
 * around the public setAttribute() stopped subclasses from overriding it, and
 * the pluck() accessibility guard fired before magic accessors got a chance to
 * answer. The rest predate that pass.
 */
class ReviewFindingsRoundTwoTest extends TestCase
{
    // -----------------------------------------------------------------
    // Overridden setAttribute() must see every input path
    // -----------------------------------------------------------------

    public function test_overridden_set_attribute_runs_for_every_input_path(): void
    {
        $direct = (new UppercasingSetterDTO([]))->setAttribute('name', 'ada');
        $constructed = new UppercasingSetterDTO(['name' => 'ada']);
        $merged = (new UppercasingSetterDTO([]))->merge(['name' => 'ada']);
        $bulk = (new UppercasingSetterDTO([]))->setAttributes(['name' => 'ada']);

        $this->assertSame('ADA', $direct->name, 'direct setAttribute()');
        $this->assertSame('ADA', $constructed->name, 'constructor');
        $this->assertSame('ADA', $merged->name, 'merge()');
        $this->assertSame('ADA', $bulk->name, 'setAttributes()');
    }

    public function test_overridden_set_attribute_sees_mapped_keys_once(): void
    {
        // The override receives the incoming key; mapping still resolves it to
        // the property exactly once, so a chained map does not take two hops.
        $dto = new UppercasingSetterDTO(['a' => 'value']);

        $this->assertSame('value', $dto->b, 'a -> b must not continue on to c.');
        $this->assertSame('', $dto->c);
    }

    public function test_prioritized_attributes_still_run_first_through_a_mapped_key(): void
    {
        $dto = new UppercasingSetterDTO(['dependent' => 'second', 'source_key' => 'first']);

        $this->assertSame(
            ['source', 'dependent'],
            $dto->assignmentOrder,
            'A prioritized property must be assigned first even when named by a mapped key.'
        );
    }

    // -----------------------------------------------------------------
    // pluck() and magic accessors
    // -----------------------------------------------------------------

    public function test_pluck_reads_a_property_exposed_through_magic_accessors(): void
    {
        $plucked = (new Collection([new MagicAccessorDTO([])]))->pluck('hidden')->all();

        $this->assertSame(
            ['magic-value'],
            $plucked,
            'A property readable through __isset()/__get() must still be readable.'
        );
    }

    public function test_pluck_still_rejects_a_genuinely_inaccessible_property(): void
    {
        $this->expectException(LogicException::class);

        (new Collection([new NonPublicPropertyDTO([])]))->pluck('secret');
    }

    public function test_pluck_still_returns_null_for_an_absent_key(): void
    {
        $this->assertSame(
            [null],
            (new Collection([new MagicAccessorDTO([])]))->pluck('nothingNamedThis')->all()
        );
    }

    // -----------------------------------------------------------------
    // Immutable with() preserves subclass-private state
    // -----------------------------------------------------------------

    public function test_with_preserves_subclass_private_state(): void
    {
        $original = new PrivateStateImmutableDTO(['label' => 'x']);
        $original->stamp('runtime-value');

        $copy = $original->with(['label' => 'y']);

        $this->assertSame('y', $copy->label);
        $this->assertSame(
            'runtime-value',
            $copy->secret(),
            'A private property declared on the subclass must survive with().'
        );
    }

    public function test_with_preserves_an_uninitialized_typed_private_property(): void
    {
        $original = new PrivateStateImmutableDTO(['label' => 'x']);
        $original->tag('runtime-tag');

        $copy = $original->with(['label' => 'y']);

        $this->assertSame(
            'runtime-tag',
            $copy->token(),
            'A typed private property with no default must not be left uninitialized.'
        );
    }

    // -----------------------------------------------------------------
    // Internal keys must not reset runtime configuration
    // -----------------------------------------------------------------

    public function test_with_does_not_reset_runtime_casts_via_an_internal_key(): void
    {
        $dto = new RuntimeCastsImmutableDTO(['label' => 'x']);
        $dto->configure(['label' => 'string']);

        $copy = $dto->with(['casts' => ['ignored' => 'int'], 'label' => 'y']);

        $this->assertSame(
            ['label' => 'string'],
            $copy->currentCasts(),
            'An internal key in the input must not blank the original configuration.'
        );
    }

    // -----------------------------------------------------------------
    // Numeric mapping aliases
    // -----------------------------------------------------------------

    public function test_a_numeric_incoming_key_still_maps(): void
    {
        $dto = new NumericMapDTO(['123' => 'hit']);

        $this->assertSame(
            'hit',
            $dto->code,
            'A numeric-string incoming key must survive the map merge.'
        );
    }

    public function test_a_numeric_mapping_reverses_on_output(): void
    {
        $dto = new NumericMapDTO(['123' => 'hit']);

        $this->assertSame(['123' => 'hit'], $dto->toMappedArray());
    }

    // -----------------------------------------------------------------
    // Bulk custom casts and null elements
    // -----------------------------------------------------------------

    public function test_bulk_array_cast_skips_null_elements(): void
    {
        $dto = new BulkNullCastDTO(['tags' => ['a', null, 'c']]);

        $this->assertSame(
            ['A', null, 'C'],
            $dto->tags,
            'A null element must bypass the caster rather than be handed to it.'
        );
    }

    public function test_bulk_collection_cast_skips_null_elements(): void
    {
        $dto = new BulkNullCollectionCastDTO(['tags' => ['a', null, 'c']]);

        $this->assertInstanceOf(Collection::class, $dto->tags);
        $this->assertSame(['A', null, 'C'], $dto->tags->all());
    }

    // -----------------------------------------------------------------
    // fromJson() root-type detection
    // -----------------------------------------------------------------

    public function test_from_json_accepts_an_object_with_numeric_keys(): void
    {
        $dto = PlainFactoryDTO::fromJson('{"0":"a","1":"b"}');

        $this->assertInstanceOf(PlainFactoryDTO::class, $dto);
    }

    public function test_from_json_rejects_a_json_list(): void
    {
        $this->expectException(JsonException::class);

        PlainFactoryDTO::fromJson('[1,2]');
    }

    public function test_from_json_rejects_an_empty_json_list(): void
    {
        // Distinguishable now that the root type is read from the document
        // rather than inferred from the decoded shape.
        $this->expectException(JsonException::class);

        PlainFactoryDTO::fromJson('[]');
    }

    public function test_from_json_still_accepts_an_empty_object(): void
    {
        $this->assertInstanceOf(PlainFactoryDTO::class, PlainFactoryDTO::fromJson('{}'));
        $this->assertInstanceOf(PlainFactoryDTO::class, PlainFactoryDTO::fromJson("  \n {} "));
    }

    public function test_from_json_still_rejects_scalars_and_null(): void
    {
        foreach (['"a string"', '42', 'null', 'true'] as $json) {
            try {
                PlainFactoryDTO::fromJson($json);
                $this->fail("fromJson({$json}) should have thrown.");
            } catch (JsonException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
