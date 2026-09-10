<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YorCreative\ArgonautDTO\Tests\Fixtures\RecordingSetterDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ReentrantAfterParentDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ReentrantBeforeParentDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ReentrantBulkAfterParentDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ReentrantBulkBeforeParentDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ReentrantThrowingDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\UppercasingSetterDTO;

/**
 * Fifth review pass: re-entrancy around the canonical-key token.
 *
 * The token was a single per-object flag, so any nested setAttribute() consumed
 * whatever the outer assignment was holding. An override that touches another
 * attribute BEFORE delegating to parent::setAttribute() therefore lost mapping
 * on the nested key and had its own key re-mapped a second time.
 *
 * Every fixture here uses the same map: a -> b, b -> c, wire -> derived. So an
 * outer assignment of 'b' that is re-mapped lands in 'c', which makes the
 * failure visible rather than silent.
 */
class ReviewFindingsRoundFiveTest extends TestCase
{
    // -----------------------------------------------------------------
    // Direct re-entry
    // -----------------------------------------------------------------

    public function test_direct_reentry_before_delegating_keeps_both_assignments_correct(): void
    {
        $dto = new ReentrantBeforeParentDTO(['a' => 'outer']);

        $this->assertSame('nested', $dto->derived, 'The nested call must be mapped normally.');
        $this->assertSame('outer', $dto->b, 'The outer assignment must stay canonical.');
        $this->assertSame('', $dto->c, 'The outer key must not be mapped a second time.');
    }

    public function test_direct_reentry_after_delegating_keeps_both_assignments_correct(): void
    {
        $dto = new ReentrantAfterParentDTO(['a' => 'outer']);

        $this->assertSame('nested', $dto->derived);
        $this->assertSame('outer', $dto->b);
        $this->assertSame('', $dto->c);
    }

    // -----------------------------------------------------------------
    // Bulk re-entry
    // -----------------------------------------------------------------

    public function test_bulk_reentry_before_delegating_keeps_both_assignments_correct(): void
    {
        $dto = new ReentrantBulkBeforeParentDTO(['a' => 'outer']);

        $this->assertSame('nested', $dto->derived);
        $this->assertSame('outer', $dto->b);
        $this->assertSame('', $dto->c);
    }

    public function test_bulk_reentry_after_delegating_keeps_both_assignments_correct(): void
    {
        $dto = new ReentrantBulkAfterParentDTO(['a' => 'outer']);

        $this->assertSame('nested', $dto->derived);
        $this->assertSame('outer', $dto->b);
        $this->assertSame('', $dto->c);
    }

    // -----------------------------------------------------------------
    // Cleanup when a nested call throws
    // -----------------------------------------------------------------

    public function test_a_failed_assignment_leaves_the_same_instance_usable(): void
    {
        // Deliberately the SAME object throughout: any state a failed
        // assignment left behind would be attached to this instance, so a fresh
        // one could not detect it.
        $dto = new ReentrantThrowingDTO([]);

        try {
            $dto->setAttributes(['a' => 'outer']);
            $this->fail('The assignment should have thrown.');
        } catch (RuntimeException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame('', $dto->b, 'The failed assignment left nothing behind.');
        $this->assertSame('', $dto->c);

        // The very same instance must now map exactly as though the failed
        // assignment had never happened.
        $dto->allowNested = false;
        $dto->setAttributes(['a' => 'second']);

        $this->assertSame('second', $dto->b);
        $this->assertSame('', $dto->c, 'Leftover state would re-map this key into $c.');

        // And a direct alias assignment on the same instance still resolves.
        $dto->setMappedAttribute('wire', 'later');
        $this->assertSame('later', $dto->derived);
    }

    public function test_state_does_not_leak_between_instances(): void
    {
        $first = new ReentrantBeforeParentDTO(['a' => 'one']);
        $second = new ReentrantBeforeParentDTO(['a' => 'two']);

        $this->assertSame('one', $first->b);
        $this->assertSame('two', $second->b);
        $this->assertSame('', $first->c);
        $this->assertSame('', $second->c);
    }

    // -----------------------------------------------------------------
    // Earlier guarantees still hold
    // -----------------------------------------------------------------

    public function test_an_override_that_rewrites_the_value_still_gets_a_canonical_key(): void
    {
        // The override changes the value before delegating, so the token cannot
        // be matched on the value -- only on the key.
        $dto = new RecordingSetterDTO(['label_alias' => '  padded  ']);

        $this->assertSame('padded', $dto->label);
        $this->assertSame('', $dto->shadow, 'A second mapping pass would land the value here.');
        $this->assertSame(['label'], $dto->seenKeys);
    }

    public function test_collisions_and_chained_maps_are_unaffected(): void
    {
        $collision = new RecordingSetterDTO(['wire' => 'not-an-int', 'count' => 2]);
        $this->assertSame(2, $collision->count);
        $this->assertSame(['count'], $collision->seenKeys);

        $chained = new UppercasingSetterDTO(['a' => 'value']);
        $this->assertSame('value', $chained->b);
        $this->assertSame('', $chained->c);
    }

    public function test_every_input_path_still_reaches_the_override(): void
    {
        $this->assertSame('ADA', (new UppercasingSetterDTO(['name' => 'ada']))->name);
        $this->assertSame('ADA', (new UppercasingSetterDTO([]))->merge(['name' => 'ada'])->name);
        $this->assertSame('ADA', (new UppercasingSetterDTO([]))->setAttribute('name', 'ada')->name);
    }
}
