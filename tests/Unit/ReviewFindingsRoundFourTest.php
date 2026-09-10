<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Tests\Fixtures\ChildPrivateTokenDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NestedAliasSetterDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\RecordingSetterDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\RedeclaredReadonlyChildDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\UppercasingSetterDTO;

/**
 * Fourth review pass. Both of these refine the previous commit.
 *
 * Suppressing key mapping for the whole of setAttributes() was too broad: a
 * setter that reaches for another attribute by alias was caught by it. And
 * identifying every property by declaring class is right for private slots but
 * wrong for a redeclared public one, which shares storage with its parent.
 */
class ReviewFindingsRoundFourTest extends TestCase
{
    // -----------------------------------------------------------------
    // Nested direct setters still map their aliases
    // -----------------------------------------------------------------

    public function test_a_nested_direct_setter_maps_its_alias_during_construction(): void
    {
        $dto = new NestedAliasSetterDTO(['source_key' => 'A']);

        $this->assertSame('A', $dto->source);
        $this->assertSame(
            'from-A',
            $dto->derived,
            'A setter calling setAttribute() by alias must still be mapped during bulk input.'
        );
    }

    public function test_a_nested_direct_setter_maps_its_alias_during_merge(): void
    {
        $dto = (new NestedAliasSetterDTO([]))->merge(['source_key' => 'B']);

        $this->assertSame('B', $dto->source);
        $this->assertSame('from-B', $dto->derived);
    }

    public function test_a_nested_direct_setter_maps_its_alias_through_with(): void
    {
        $dto = (new NestedAliasSetterDTO([]))->with(['source_key' => 'C']);

        $this->assertSame('C', $dto->source);
        $this->assertSame('from-C', $dto->derived);
    }

    public function test_a_nested_direct_setter_still_works_on_a_direct_call(): void
    {
        $dto = (new NestedAliasSetterDTO([]))->setAttribute('source', 'D');

        $this->assertSame('D', $dto->source);
        $this->assertSame('from-D', $dto->derived);
    }

    // -----------------------------------------------------------------
    // The narrower suppression must not undo the earlier guarantees
    // -----------------------------------------------------------------

    public function test_collisions_are_still_settled_before_assignment(): void
    {
        $dto = new RecordingSetterDTO(['wire' => 'not-an-int', 'count' => 2]);

        $this->assertSame(2, $dto->count);
        $this->assertSame(['count'], $dto->seenKeys, 'Still exactly one assignment.');
    }

    public function test_overrides_still_receive_canonical_keys(): void
    {
        $dto = new RecordingSetterDTO(['label_alias' => '  padded  ']);

        $this->assertSame('padded', $dto->label);
        $this->assertSame('', $dto->shadow, 'A second mapping pass would land the value here.');
        $this->assertSame(['label'], $dto->seenKeys);
    }

    public function test_chained_maps_still_take_exactly_one_hop(): void
    {
        $dto = new UppercasingSetterDTO(['a' => 'value']);

        $this->assertSame('value', $dto->b);
        $this->assertSame('', $dto->c);
    }

    // -----------------------------------------------------------------
    // Redeclared readonly properties share one slot
    // -----------------------------------------------------------------

    public function test_with_copies_a_redeclared_readonly_property_once(): void
    {
        $dto = new RedeclaredReadonlyChildDTO(['shared' => 'v', 'other' => 'o']);

        $copy = $dto->with([]);

        $this->assertSame('v', $copy->shared, 'A redeclared readonly property is one slot, not two.');
        $this->assertSame('o', $copy->other);
    }

    public function test_with_can_change_a_redeclared_readonly_property(): void
    {
        $dto = new RedeclaredReadonlyChildDTO(['shared' => 'v', 'other' => 'o']);

        $copy = $dto->with(['shared' => 'w']);

        $this->assertSame('w', $copy->shared);
        $this->assertSame('o', $copy->other);
    }

    public function test_same_named_private_slots_are_still_kept_apart(): void
    {
        // The narrowing must not regress the previous fix: private properties
        // of the same name really are distinct slots.
        $dto = new ChildPrivateTokenDTO(['label' => 'x']);
        $dto->stampParent('parent-value');
        $dto->stampChild('child-value');

        $copy = $dto->with([]);

        $this->assertSame('parent-value', $copy->parentToken());
        $this->assertSame('child-value', $copy->childToken());
    }
}
