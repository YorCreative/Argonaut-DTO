<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YorCreative\ArgonautDTO\CircularReferenceException;
use YorCreative\ArgonautDTO\Tests\Fixtures\BasketDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NodeDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\PairDTO;

final class CircularReferenceTest extends TestCase
{
    public function test_a_direct_self_reference_is_reported_as_a_circular_reference(): void
    {
        $node = new NodeDTO(['label' => 'root']);
        $node->child = $node;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage('circular reference');

        $node->toArray();
    }

    public function test_a_circular_reference_names_the_offending_class(): void
    {
        $node = new NodeDTO(['label' => 'root']);
        $node->child = $node;

        $this->expectExceptionMessage(NodeDTO::class);

        $node->toArray();
    }

    public function test_a_longer_cycle_is_detected(): void
    {
        $first = new NodeDTO(['label' => 'first']);
        $second = new NodeDTO(['label' => 'second']);
        $first->child = $second;
        $second->child = $first;

        $this->expectException(CircularReferenceException::class);

        $first->toArray();
    }

    public function test_a_self_reference_inside_an_array_is_detected(): void
    {
        $basket = new BasketDTO(['label' => 'root']);
        $basket->items = [$basket];

        $this->expectException(CircularReferenceException::class);

        $basket->toArray();
    }

    public function test_a_circular_reference_is_a_runtime_exception(): void
    {
        $node = new NodeDTO(['label' => 'root']);
        $node->child = $node;

        $this->expectException(RuntimeException::class);

        $node->toArray();
    }

    public function test_to_json_also_detects_circular_references(): void
    {
        $node = new NodeDTO(['label' => 'root']);
        $node->child = $node;

        $this->expectException(CircularReferenceException::class);

        $node->toJson();
    }

    public function test_the_same_dto_in_sibling_branches_is_not_a_circular_reference(): void
    {
        $shared = new NodeDTO(['label' => 'shared']);
        $pair = new PairDTO;
        $pair->left = $shared;
        $pair->right = $shared;

        self::assertSame(
            ['left' => ['label' => 'shared', 'child' => null], 'right' => ['label' => 'shared', 'child' => null]],
            $pair->toArray(),
        );
    }

    public function test_the_same_dto_repeated_in_one_array_is_not_a_circular_reference(): void
    {
        $shared = new NodeDTO(['label' => 'shared']);
        $basket = new BasketDTO(['label' => 'root']);
        $basket->items = [$shared, $shared];

        $array = $basket->toArray();

        self::assertSame('shared', $array['items'][0]['label']);
        self::assertSame('shared', $array['items'][1]['label']);
    }

    public function test_a_dto_can_be_serialized_again_after_a_failed_attempt(): void
    {
        $node = new NodeDTO(['label' => 'root']);
        $node->child = $node;

        try {
            $node->toArray();
        } catch (CircularReferenceException) {
            // The guard must be released even though serialization failed.
        }

        $node->child = null;

        self::assertSame(['label' => 'root', 'child' => null], $node->toArray());
    }

    public function test_the_exception_exposes_the_offending_dto_class(): void
    {
        $node = new NodeDTO(['label' => 'root']);
        $node->child = $node;

        try {
            $node->toArray();
            self::fail('Expected a CircularReferenceException.');
        } catch (CircularReferenceException $exception) {
            self::assertSame(NodeDTO::class, $exception->dtoClass());
        }
    }

    public function test_repeated_serialization_of_the_same_dto_keeps_working(): void
    {
        $node = NodeDTO::chain(3);

        self::assertSame($node->toArray(), $node->toArray());
    }
}
