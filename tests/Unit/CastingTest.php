<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use ArrayIterator;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\AccountDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\AssembledCollectionDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\CatalogDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\LooseCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\Money;
use YorCreative\ArgonautDTO\Tests\Fixtures\NodeDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\PriceDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ProfileDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\SluggedDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\Status;
use YorCreative\ArgonautDTO\Tests\Fixtures\StrictArrayDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\TagDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\UnresolvableAssemblerDTO;

final class CastingTest extends TestCase
{
    public function test_the_collection_shorthand_casts_items(): void
    {
        $catalog = new CatalogDTO(['tags' => [['name' => 'php'], ['name' => 'dto']]]);

        self::assertInstanceOf(Collection::class, $catalog->tags);
        self::assertCount(2, $catalog->tags);
        self::assertInstanceOf(TagDTO::class, $catalog->tags->first());
        self::assertSame('php', $catalog->tags->first()->name);
    }

    public function test_scalar_casts_coerce_input(): void
    {
        $catalog = new CatalogDTO(['total' => '42', 'active' => 1, 'rating' => '4.5']);

        self::assertSame(42, $catalog->total);
        self::assertTrue($catalog->active);
        self::assertSame(4.5, $catalog->rating);
    }

    public function test_array_casts_build_enums_from_backing_values(): void
    {
        $catalog = new CatalogDTO(['statuses' => ['active', 'archived']]);

        self::assertSame([Status::Active, Status::Archived], $catalog->statuses);
    }

    public function test_enum_instances_pass_through_untouched(): void
    {
        $catalog = new CatalogDTO(['statuses' => [Status::Active]]);

        self::assertSame([Status::Active], $catalog->statuses);
    }

    public function test_array_casts_accept_a_traversable(): void
    {
        $dto = new StrictArrayDTO(['tags' => new ArrayIterator([['name' => 'php']])]);

        self::assertInstanceOf(TagDTO::class, $dto->tags[0]);
    }

    public function test_array_casts_accept_a_collection(): void
    {
        $dto = new StrictArrayDTO(['tags' => new Collection([['name' => 'php']])]);

        self::assertInstanceOf(TagDTO::class, $dto->tags[0]);
    }

    public function test_array_casts_accept_objects_as_items(): void
    {
        $dto = new StrictArrayDTO(['tags' => [(object) ['name' => 'php']]]);

        self::assertSame('php', $dto->tags[0]->name);
    }

    public function test_already_cast_items_are_reused(): void
    {
        $tag = new TagDTO(['name' => 'php']);
        $dto = new StrictArrayDTO(['tags' => [$tag]]);

        self::assertSame($tag, $dto->tags[0]);
    }

    public function test_non_iterable_input_for_an_array_cast_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string must be iterable to cast to array.');

        new StrictArrayDTO(['tags' => 'php']);
    }

    public function test_non_iterable_input_for_a_collection_cast_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('int must be iterable to cast to collection.');

        new CatalogDTO(['tags' => 7]);
    }

    public function test_date_time_strings_are_cast(): void
    {
        $node = new class(['when' => '2026-01-02 03:04:05']) extends ArgonautDTO
        {
            public ?DateTimeImmutable $when = null;

            /** @var array<string, string> */
            protected array $casts = ['when' => DateTimeImmutable::class];
        };

        self::assertSame('2026-01-02 03:04:05', $node->when?->format('Y-m-d H:i:s'));
    }

    public function test_existing_date_time_instances_pass_through(): void
    {
        $date = new DateTimeImmutable('2026-01-02');

        $node = new class(['when' => $date]) extends ArgonautDTO
        {
            public ?DateTimeImmutable $when = null;

            /** @var array<string, string> */
            protected array $casts = ['when' => DateTimeImmutable::class];
        };

        self::assertSame($date, $node->when);
    }

    public function test_single_model_casts_accept_an_object(): void
    {
        $node = new NodeDTO(['label' => 'root', 'child' => (object) ['label' => 'leaf']]);

        self::assertSame('leaf', $node->child?->label);
    }

    public function test_null_values_bypass_casting(): void
    {
        $node = new NodeDTO(['label' => 'root', 'child' => null]);

        self::assertNull($node->child);
    }

    public function test_unknown_keys_are_ignored(): void
    {
        $tag = new TagDTO(['name' => 'php', 'nope' => 'ignored']);

        self::assertSame(['name' => 'php'], $tag->toArray());
    }

    public function test_prioritized_attributes_are_applied_first(): void
    {
        $dto = new SluggedDTO(['slug' => 'v1', 'title' => 'Hello World']);

        self::assertSame('hello-world-v1', $dto->slug);
    }

    public function test_nested_assemblers_transform_a_single_value(): void
    {
        $account = new AccountDTO(['owner' => ['first' => 'Jane', 'last' => 'Doe']]);

        self::assertInstanceOf(ProfileDTO::class, $account->owner);
        self::assertSame('Jane Doe', $account->owner->fullName);
    }

    public function test_nested_assemblers_transform_each_item_of_an_array(): void
    {
        $account = new AccountDTO([
            'members' => [
                ['first' => 'Jane', 'last' => 'Doe'],
                ['first' => 'Alex', 'last' => 'Roe'],
            ],
        ]);

        self::assertSame('Jane Doe', $account->members[0]->fullName);
        self::assertSame('Alex Roe', $account->members[1]->fullName);
    }

    public function test_a_scalar_item_cannot_be_cast_to_a_dto(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string cannot be cast to '.TagDTO::class);

        new StrictArrayDTO(['tags' => ['just-a-string']]);
    }

    public function test_a_scalar_cannot_be_cast_to_a_single_dto(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be cast to '.NodeDTO::class);

        new NodeDTO(['label' => 'root', 'child' => 'oops']);
    }

    public function test_a_scalar_item_routed_through_a_nested_assembler_is_reported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be cast to '.ProfileDTO::class);

        new AccountDTO(['members' => ['already-a-scalar']]);
    }

    public function test_a_scalar_single_value_routed_through_a_nested_assembler_is_reported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be cast to '.ProfileDTO::class);

        new AccountDTO(['owner' => 'already-a-scalar']);
    }

    public function test_value_objects_with_scalar_constructors_still_cast(): void
    {
        $price = new PriceDTO(['total' => '19.99']);

        self::assertInstanceOf(Money::class, $price->total);
        self::assertSame('19.99', $price->total->amount);
    }

    public function test_get_attributes_to_update_omits_internal_properties(): void
    {
        $attributes = (new CatalogDTO(['total' => 3]))->getAttributesToUpdate();

        self::assertSame(3, $attributes['total']);
        self::assertArrayNotHasKey('casts', $attributes);
        self::assertArrayNotHasKey('prioritizedAttributes', $attributes);
        self::assertArrayNotHasKey('nestedAssemblers', $attributes);
    }

    public function test_the_static_collection_factory_builds_dtos(): void
    {
        $tags = TagDTO::collection([['name' => 'php'], ['name' => 'dto']]);

        self::assertInstanceOf(Collection::class, $tags);
        self::assertCount(2, $tags);
        self::assertInstanceOf(TagDTO::class, $tags->first());
        self::assertSame('dto', $tags[1]->name);
    }

    public function test_setters_take_precedence_over_direct_assignment(): void
    {
        $dto = new SluggedDTO(['title' => 'Hello World']);

        self::assertSame('Hello World', $dto->title);
    }

    public function test_an_unresolvable_cast_entry_leaves_input_untouched(): void
    {
        $dto = new LooseCastDTO(['emptyCast' => 'raw', 'unknownCast' => 'also-raw']);

        self::assertSame('raw', $dto->emptyCast);
        self::assertSame('also-raw', $dto->unknownCast);
    }

    public function test_a_nested_assembler_without_a_target_class_is_skipped(): void
    {
        $dto = new UnresolvableAssemblerDTO(['label' => 'plain']);

        self::assertSame('plain', $dto->label);
    }

    public function test_a_nested_assembler_feeds_the_collection_shorthand(): void
    {
        $dto = new AssembledCollectionDTO([
            'profiles' => [
                ['first' => 'Jane', 'last' => 'Doe'],
                ['first' => 'Alex', 'last' => 'Roe'],
            ],
        ]);

        self::assertCount(2, $dto->profiles);
        self::assertSame('Jane Doe', $dto->profiles[0]->fullName);
        self::assertSame('Alex Roe', $dto->profiles[1]->fullName);
    }

    public function test_a_collection_item_is_unwrapped_before_casting(): void
    {
        $dto = new StrictArrayDTO(['tags' => [new Collection(['name' => 'php'])]]);

        self::assertSame('php', $dto->tags[0]->name);
    }

    public function test_a_traversable_item_is_unwrapped_before_casting(): void
    {
        $dto = new StrictArrayDTO(['tags' => [new ArrayIterator(['name' => 'php'])]]);

        self::assertSame('php', $dto->tags[0]->name);
    }

    public function test_a_collection_is_unwrapped_for_a_single_model_cast(): void
    {
        $node = new NodeDTO(['label' => 'root', 'child' => new Collection(['label' => 'leaf'])]);

        self::assertSame('leaf', $node->child?->label);
    }
}
