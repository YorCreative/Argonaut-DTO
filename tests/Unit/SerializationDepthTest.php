<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YorCreative\ArgonautDTO\Tests\Fixtures\BadUtf8DTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\BranchDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NodeDTO;

final class SerializationDepthTest extends TestCase
{
    public function test_to_array_preserves_deeply_nested_dtos_by_default(): void
    {
        $array = NodeDTO::chain(6)->toArray();

        self::assertSame(
            'L6',
            $array['child']['child']['child']['child']['child']['label'],
        );
    }

    public function test_to_json_preserves_deeply_nested_dtos_by_default(): void
    {
        $decoded = json_decode(NodeDTO::chain(6)->toJson(), true);

        self::assertSame(
            'L6',
            $decoded['child']['child']['child']['child']['child']['label'],
        );
    }

    public function test_json_serialize_preserves_deeply_nested_dtos_by_default(): void
    {
        $decoded = json_decode((string) json_encode(NodeDTO::chain(6)), true);

        self::assertSame(
            'L6',
            $decoded['child']['child']['child']['child']['child']['label'],
        );
    }

    public function test_to_json_accepts_an_explicit_depth(): void
    {
        $decoded = json_decode(NodeDTO::chain(2)->toJson(depth: 2), true);

        self::assertSame('L2', $decoded['child']['label']);
    }

    public function test_to_array_throws_when_the_depth_limit_is_reached(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exceeded the maximum serialization depth');

        NodeDTO::chain(4)->toArray(2);
    }

    public function test_to_json_encodes_structures_deeper_than_the_encoder_default(): void
    {
        // Each DTO level costs two array levels once serialized, so 300 levels
        // produces ~600 — past json_encode()'s own 512 limit, but well inside
        // the walk limit that permitted it. json_decode() caps at 512 as well,
        // hence the explicit depth on the way back in.
        $decoded = json_decode(BranchDTO::nest(300)->toJson(), true, 4096);

        self::assertIsArray($decoded);

        $cursor = $decoded;
        for ($level = 1; $level < 300; $level++) {
            $cursor = $cursor['children'][0];
        }

        self::assertSame('L300', $cursor['label']);
    }

    public function test_a_deep_structure_that_cannot_be_encoded_still_reports_the_encoding_error(): void
    {
        $dto = BranchDTO::nest(300);

        $cursor = $dto;
        while ($cursor->children !== []) {
            $cursor = $cursor->children[0];
        }
        $cursor->label = "\xB1\x31";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON error: Malformed UTF-8 characters');

        $dto->toJson();
    }

    public function test_json_serialize_matches_to_json_for_deep_structures(): void
    {
        $dto = BranchDTO::nest(300);

        self::assertSame($dto->toJson(), (string) json_encode($dto->toArray(), 0, 4096));
    }

    public function test_to_json_reports_encoding_failures(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON error: Malformed UTF-8 characters');

        (new BadUtf8DTO)->toJson();
    }
}
