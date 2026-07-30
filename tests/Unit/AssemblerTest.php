<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use BadFunctionCallException;
use BadMethodCallException;
use Generator;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\EmptyAssembler;
use YorCreative\ArgonautDTO\Tests\Fixtures\LegacyAssembler;
use YorCreative\ArgonautDTO\Tests\Fixtures\PrefixingAssembler;
use YorCreative\ArgonautDTO\Tests\Fixtures\ProfileAssembler;
use YorCreative\ArgonautDTO\Tests\Fixtures\ProfileDTO;

final class AssemblerTest extends TestCase
{
    public function test_it_assembles_from_an_array(): void
    {
        $profile = ProfileAssembler::assemble(['first' => 'Jane', 'last' => 'Doe'], ProfileDTO::class);

        self::assertInstanceOf(ProfileDTO::class, $profile);
        self::assertSame('Jane Doe', $profile->fullName);
    }

    public function test_it_assembles_from_an_object(): void
    {
        $profile = ProfileAssembler::assemble((object) ['first' => 'Jane', 'last' => 'Doe'], ProfileDTO::class);

        self::assertSame('Jane Doe', $profile->fullName);
    }

    public function test_array_assemble_is_an_alias(): void
    {
        $profile = ProfileAssembler::arrayAssemble(['first' => 'Jane', 'last' => 'Doe'], ProfileDTO::class);

        self::assertSame('Jane Doe', $profile->fullName);
    }

    public function test_from_array_preserves_keys(): void
    {
        $profiles = ProfileAssembler::fromArray([
            'owner' => ['first' => 'Jane', 'last' => 'Doe'],
            'member' => ['first' => 'Alex', 'last' => 'Roe'],
        ], ProfileDTO::class);

        self::assertInstanceOf(Collection::class, $profiles);
        self::assertSame('Jane Doe', $profiles['owner']->fullName);
        self::assertSame('Alex Roe', $profiles['member']->fullName);
    }

    public function test_from_collection_accepts_a_generator(): void
    {
        $source = (function (): Generator {
            yield ['first' => 'Jane', 'last' => 'Doe'];
            yield ['first' => 'Alex', 'last' => 'Roe'];
        })();

        $profiles = ProfileAssembler::fromCollection($source, ProfileDTO::class);

        self::assertCount(2, $profiles);
        self::assertSame('Alex Roe', $profiles[1]->fullName);
    }

    public function test_from_collection_accepts_the_package_collection(): void
    {
        $profiles = ProfileAssembler::fromCollection(
            new Collection([['first' => 'Jane', 'last' => 'Doe']]),
            ProfileDTO::class,
        );

        self::assertSame('Jane Doe', $profiles[0]->fullName);
    }

    public function test_it_falls_back_to_the_from_prefix(): void
    {
        $profile = LegacyAssembler::assemble(['name' => 'Jane Doe'], ProfileDTO::class);

        self::assertSame('Jane Doe', $profile->fullName);
    }

    public function test_a_missing_assembly_method_is_reported(): void
    {
        $this->expectException(BadFunctionCallException::class);
        $this->expectExceptionMessage('Missing method [toProfileDTO] or [fromProfileDTO]');

        EmptyAssembler::assemble(['name' => 'Jane'], ProfileDTO::class);
    }

    public function test_instance_methods_run_with_a_supplied_instance(): void
    {
        $profile = PrefixingAssembler::assemble(
            ['name' => 'Jane Doe'],
            ProfileDTO::class,
            new PrefixingAssembler('Prof. '),
        );

        self::assertSame('Prof. Jane Doe', $profile->fullName);
    }

    public function test_assemble_instance_uses_the_current_object(): void
    {
        $profile = (new PrefixingAssembler('Dr. '))->assembleInstance(['name' => 'Jane Doe'], ProfileDTO::class);

        self::assertSame('Dr. Jane Doe', $profile->fullName);
    }

    public function test_instance_methods_without_an_instance_are_rejected(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('without an instance');

        PrefixingAssembler::assemble(['name' => 'Jane Doe'], ProfileDTO::class);
    }
}
