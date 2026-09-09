<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\DerivedNameDTO;
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
}
