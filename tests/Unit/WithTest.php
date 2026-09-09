<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\DerivedNameDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ImmutableAssembledDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ImmutableCastedHolderDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ImmutableCustomCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ImmutableHolderDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ImmutablePointDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ProfileDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\TagDTO;

final class WithTest extends TestCase
{
    public function test_with_returns_a_new_instance(): void
    {
        $dto = new ProfileDTO(['fullName' => 'Ada']);

        $updated = $dto->with(['fullName' => 'Grace']);

        self::assertNotSame($dto, $updated);
        self::assertInstanceOf(ProfileDTO::class, $updated);
    }

    public function test_with_does_not_mutate_the_original(): void
    {
        $dto = new ProfileDTO(['fullName' => 'Ada']);

        $dto->with(['fullName' => 'Grace']);

        self::assertSame('Ada', $dto->fullName);
    }

    public function test_with_applies_the_changes(): void
    {
        $updated = (new ProfileDTO(['fullName' => 'Ada']))->with(['fullName' => 'Grace']);

        self::assertSame('Grace', $updated->fullName);
    }

    public function test_with_preserves_untouched_properties(): void
    {
        $dto = new DerivedNameDTO(['firstName' => 'Jane', 'lastName' => 'Doe']);

        $updated = $dto->with(['lastName' => 'Smith']);

        self::assertSame('Jane', $updated->firstName);
    }

    public function test_with_recomputes_setter_derived_properties(): void
    {
        // THE DISCRIMINATING TEST. A with() implemented as a full-state rebuild
        // recomputes fullName in the prioritized pass and then clobbers it with
        // the stale value in the remaining pass, yielding 'Jane Doe'.
        $dto = new DerivedNameDTO(['firstName' => 'Jane', 'lastName' => 'Doe']);
        self::assertSame('Jane Doe', $dto->fullName);

        $updated = $dto->with(['lastName' => 'Smith']);

        self::assertSame('Jane Smith', $updated->fullName);
        self::assertSame('Jane Doe', $dto->fullName);
    }

    public function test_with_applies_casting_to_the_changes(): void
    {
        $updated = (new ProfileDTO(['fullName' => 'Ada']))->with(['fullName' => 123]);

        self::assertSame('123', $updated->fullName);
    }

    public function test_with_no_changes_returns_an_equal_copy(): void
    {
        $dto = new DerivedNameDTO(['firstName' => 'Jane', 'lastName' => 'Doe']);

        $copy = $dto->with([]);

        self::assertNotSame($dto, $copy);
        self::assertSame('Jane Doe', $copy->fullName);
    }

    public function test_with_is_a_shallow_copy(): void
    {
        // Documented behavior: nested objects are shared with the original.
        $tag = new TagDTO(['name' => 'shared']);
        $dto = new class(['tag' => $tag]) extends ArgonautDTO
        {
            public ?TagDTO $tag = null;
        };

        $copy = $dto->with([]);

        self::assertSame($tag, $copy->tag);
    }

    public function test_with_returns_a_new_immutable_instance(): void
    {
        $point = new ImmutablePointDTO(['x' => 1, 'y' => 2]);

        $moved = $point->with(['y' => 9]);

        self::assertNotSame($point, $moved);
        self::assertInstanceOf(ImmutablePointDTO::class, $moved);
    }

    public function test_with_preserves_unchanged_readonly_properties(): void
    {
        $moved = (new ImmutablePointDTO(['x' => 1, 'y' => 2]))->with(['y' => 9]);

        self::assertSame(1, $moved->x);
        self::assertSame(9, $moved->y);
    }

    public function test_with_does_not_mutate_the_original_immutable_dto(): void
    {
        $point = new ImmutablePointDTO(['x' => 1, 'y' => 2]);

        $point->with(['y' => 9]);

        self::assertSame(2, $point->y);
    }

    public function test_with_on_an_immutable_dto_accepts_no_changes(): void
    {
        $copy = (new ImmutablePointDTO(['x' => 1, 'y' => 2]))->with([]);

        self::assertSame(1, $copy->x);
        self::assertSame(2, $copy->y);
    }

    public function test_with_produces_a_copy_free_of_internal_properties(): void
    {
        $copy = (new ImmutablePointDTO(['x' => 1, 'y' => 2]))->with(['x' => 5]);

        self::assertSame(['x', 'y'], array_keys($copy->toArray()));
        self::assertSame(5, $copy->x);
        self::assertSame(2, $copy->y);
    }

    public function test_with_carries_forward_an_uninitialised_property(): void
    {
        $partial = new ImmutablePointDTO(['x' => 1]); // y never initialised

        $copy = $partial->with(['x' => 7]);

        self::assertSame(7, $copy->x);

        $this->expectException(\Error::class);
        $copy->y;
    }

    public function test_with_on_an_immutable_dto_is_a_shallow_copy(): void
    {
        $tag = new TagDTO(['name' => 'shared']);

        $copy = (new ImmutableHolderDTO(['tag' => $tag]))->with([]);

        self::assertSame($tag, $copy->tag);
    }

    public function test_with_does_not_reapply_a_custom_cast_to_an_unchanged_value(): void
    {
        // Regression test for the silent-corruption defect: a with() that
        // rebuilds from full state would send 'amount' through HalvingCast a
        // second time, turning 50 into 12.5 instead of leaving it at 25.
        $dto = new ImmutableCustomCastDTO(['amount' => 50, 'label' => 'a']);
        self::assertSame(25, $dto->amount);

        $copy = $dto->with([]);

        self::assertSame(25, $copy->amount);
    }

    public function test_with_does_not_reassemble_an_already_assembled_nested_dto(): void
    {
        $dto = new ImmutableAssembledDTO(['profile' => ['first' => 'Ada', 'last' => 'Lovelace']]);
        self::assertSame('Ada Lovelace', $dto->profile->fullName);

        $copy = $dto->with([]);

        self::assertSame('Ada Lovelace', $copy->profile->fullName);
    }

    public function test_with_applies_a_custom_cast_to_a_changed_value(): void
    {
        $dto = new ImmutableCustomCastDTO(['amount' => 50, 'label' => 'a']);

        $copy = $dto->with(['amount' => 10]);

        self::assertSame(5, $copy->amount);
    }

    public function test_with_preserves_a_built_in_model_cast_instance(): void
    {
        $dto = new ImmutableCastedHolderDTO(['tag' => ['name' => 'shared']]);

        $copy = $dto->with([]);

        self::assertSame($dto->tag, $copy->tag);
    }
}
