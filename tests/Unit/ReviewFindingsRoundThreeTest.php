<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\ChildPrivateTokenDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\MagicAccessorDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NestedBulkAssignmentDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NonPublicPropertyDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NullableMagicAccessorDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\RecordingSetterDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\UppercasingSetterDTO;

/**
 * Third review pass. All three of these refine fixes from the previous two.
 *
 * Mapping has to resolve collisions on the whole input *before* anything is
 * assigned, and dispatch canonical property names to setAttribute() -- while
 * still mapping exactly once and still running a subclass's override.
 */
class ReviewFindingsRoundThreeTest extends TestCase
{
    // -----------------------------------------------------------------
    // Collisions resolve before assignment
    // -----------------------------------------------------------------

    public function test_a_losing_collision_value_never_reaches_the_property(): void
    {
        // 'wire' maps to 'count'. Both appear, so one value loses. The loser is
        // discarded while still an array key -- it must never be assigned, or
        // an invalid value hits a typed property and raises a TypeError before
        // the winner arrives.
        $dto = new RecordingSetterDTO(['wire' => 'not-an-int', 'count' => 2]);

        $this->assertSame(2, $dto->count, 'The later key wins the collision.');
    }

    public function test_the_losing_value_is_discarded_in_either_order(): void
    {
        $dto = new RecordingSetterDTO(['count' => 2, 'wire' => 7]);

        $this->assertSame(7, $dto->count, 'The later key still wins when the alias is second.');
    }

    public function test_a_collision_assigns_the_property_exactly_once(): void
    {
        $dto = new RecordingSetterDTO(['wire' => 1, 'count' => 2]);

        $this->assertSame(
            ['count'],
            $dto->seenKeys,
            'The colliding pair must produce a single assignment, not two.'
        );
    }

    // -----------------------------------------------------------------
    // Overrides receive canonical property names
    // -----------------------------------------------------------------

    public function test_an_override_receives_the_canonical_property_name(): void
    {
        $dto = new RecordingSetterDTO(['wire' => 5]);

        $this->assertSame(
            ['count'],
            $dto->seenKeys,
            'An override keyed on the property name cannot normalise if it is handed the alias.'
        );
        $this->assertSame(5, $dto->count);
    }

    public function test_property_specific_normalisation_in_an_override_still_applies(): void
    {
        // normalise() keys off the canonical name, which is the whole point of
        // dispatching canonical keys.
        $dto = new RecordingSetterDTO(['label_alias' => '  padded  ']);

        $this->assertSame('padded', $dto->label, 'The override normalised by property name.');
        $this->assertSame('', $dto->shadow, 'A second mapping pass would land the value here.');
    }

    public function test_a_direct_alias_assignment_goes_through_set_mapped_attribute(): void
    {
        // setMappedAttribute() resolves the alias and then hands the property
        // name to setAttribute(), so the override sees the canonical name here
        // too -- exactly as it does for bulk input.
        $dto = (new RecordingSetterDTO([]))->setMappedAttribute('wire', 3);

        $this->assertSame(3, $dto->count);
        $this->assertSame(['count'], $dto->seenKeys);
    }

    public function test_chained_maps_still_take_exactly_one_hop(): void
    {
        $dto = new UppercasingSetterDTO(['a' => 'value']);

        $this->assertSame('value', $dto->b);
        $this->assertSame('', $dto->c);
    }

    public function test_overridden_set_attribute_still_runs_for_every_input_path(): void
    {
        $this->assertSame('ADA', (new UppercasingSetterDTO(['name' => 'ada']))->name);
        $this->assertSame('ADA', (new UppercasingSetterDTO([]))->merge(['name' => 'ada'])->name);
        $this->assertSame('ADA', (new UppercasingSetterDTO([]))->setAttributes(['name' => 'ada'])->name);
        $this->assertSame('ADA', (new UppercasingSetterDTO([]))->setAttribute('name', 'ada')->name);
    }

    public function test_a_nested_bulk_assignment_keeps_the_outer_guard(): void
    {
        // The override re-enters setAttributes(). The inner call must not clear
        // the guard the outer call is relying on, or the outer call's remaining
        // keys would be mapped a second time.
        $dto = new NestedBulkAssignmentDTO(['alias' => 'value', 'a' => 'outer']);

        $this->assertSame('value', $dto->primary);
        $this->assertSame('from-value', $dto->derived);
        $this->assertSame('outer', $dto->b, 'The outer chained key still took exactly one hop.');
        $this->assertSame('', $dto->c);
    }

    // -----------------------------------------------------------------
    // Same-named private properties across inheritance
    // -----------------------------------------------------------------

    public function test_with_preserves_both_private_slots_of_the_same_name(): void
    {
        $dto = new ChildPrivateTokenDTO(['label' => 'x']);
        $dto->stampParent('parent-value');
        $dto->stampChild('child-value');

        $copy = $dto->with([]);

        $this->assertSame(
            'parent-value',
            $copy->parentToken(),
            "The parent's private slot is a distinct property and must be copied too."
        );
        $this->assertSame('child-value', $copy->childToken());
    }

    public function test_with_still_replaces_the_changed_property(): void
    {
        $dto = new ChildPrivateTokenDTO(['label' => 'x']);
        $dto->stampParent('parent-value');
        $dto->stampChild('child-value');

        $copy = $dto->with(['label' => 'y']);

        $this->assertSame('y', $copy->label);
        $this->assertSame('parent-value', $copy->parentToken());
        $this->assertSame('child-value', $copy->childToken());
    }

    // -----------------------------------------------------------------
    // pluck() and a magic property holding null
    // -----------------------------------------------------------------

    public function test_pluck_returns_null_for_an_exposed_null_property(): void
    {
        // A conventional __isset() reports false for a null value, so isset()
        // alone cannot tell "not readable" from "readable and null".
        $plucked = (new Collection([new NullableMagicAccessorDTO([])]))->pluck('optional')->all();

        $this->assertSame([null], $plucked);
    }

    public function test_pluck_still_reads_an_exposed_non_null_property(): void
    {
        $plucked = (new Collection([new NullableMagicAccessorDTO([])]))->pluck('present')->all();

        $this->assertSame(['value'], $plucked);
    }

    public function test_pluck_still_rejects_a_property_with_no_accessor_at_all(): void
    {
        $this->expectException(LogicException::class);

        (new Collection([new NonPublicPropertyDTO([])]))->pluck('secret');
    }

    public function test_pluck_still_reads_the_plain_magic_fixture(): void
    {
        $this->assertSame(
            ['magic-value'],
            (new Collection([new MagicAccessorDTO([])]))->pluck('hidden')->all()
        );
    }
}
