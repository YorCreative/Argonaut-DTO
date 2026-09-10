<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\ForeignCollection;
use YorCreative\ArgonautDTO\Tests\Fixtures\ForeignCollectionCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ForeignCollectionCustomCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ForeignCollectionHolderDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\TagDTO;

/**
 * A DTO may name the collection class its `collection:` casts produce.
 *
 * The package has no framework dependency and gains none here: it instantiates
 * whatever class string collectionClass() returns and calls map() on it. The
 * only contract is a constructor taking an array, a map(callable) method, and
 * Traversable so the value can be walked on the way back out.
 */
class PluggableCollectionTest extends TestCase
{
    // -----------------------------------------------------------------
    // The default is unchanged
    // -----------------------------------------------------------------

    public function test_the_default_collection_class_is_the_packages_own(): void
    {
        $dto = new ForeignCollectionHolderDTO(['tags' => [['name' => 'a']]]);

        $this->assertInstanceOf(Collection::class, $dto->tags);
    }

    // -----------------------------------------------------------------
    // Casting produces the configured class
    // -----------------------------------------------------------------

    public function test_a_collection_cast_produces_the_configured_class(): void
    {
        $dto = new ForeignCollectionCastDTO(['tags' => [['name' => 'a'], ['name' => 'b']]]);

        $this->assertInstanceOf(ForeignCollection::class, $dto->tags);
        $this->assertNotInstanceOf(Collection::class, $dto->tags);
        $this->assertCount(2, $dto->tags->all());
        $this->assertContainsOnlyInstancesOf(TagDTO::class, $dto->tags->all());
    }

    public function test_a_custom_cast_collection_produces_the_configured_class(): void
    {
        // The `collection:<CastClass>` form is built at a second site inside the
        // casting engine; both must honour the configured class.
        $dto = new ForeignCollectionCustomCastDTO(['tags' => ['a', null, 'c']]);

        $this->assertInstanceOf(ForeignCollection::class, $dto->tags);
        $this->assertSame(['A', null, 'C'], $dto->tags->all());
    }

    // -----------------------------------------------------------------
    // Serialization
    // -----------------------------------------------------------------

    public function test_a_foreign_collection_serializes_as_an_array(): void
    {
        // toArray() walked only its own Collection, so a foreign one landed in
        // the output as a raw object instead of a nested array.
        $dto = new ForeignCollectionCastDTO(['tags' => [['name' => 'a'], ['name' => 'b']]]);

        $this->assertSame(
            ['tags' => [['name' => 'a'], ['name' => 'b']]],
            $dto->toArray()
        );
    }

    public function test_a_foreign_collection_round_trips_through_json(): void
    {
        $dto = new ForeignCollectionCastDTO(['tags' => [['name' => 'a']]]);

        $this->assertSame('{"tags":[{"name":"a"}]}', $dto->toJson());
    }

    // -----------------------------------------------------------------
    // A foreign collection as input
    // -----------------------------------------------------------------

    public function test_a_foreign_collection_is_accepted_as_input_to_a_collection_cast(): void
    {
        $dto = new ForeignCollectionCastDTO([
            'tags' => new ForeignCollection([['name' => 'a'], ['name' => 'b']]),
        ]);

        $this->assertInstanceOf(ForeignCollection::class, $dto->tags);
        $this->assertCount(2, $dto->tags->all());
    }

    public function test_a_foreign_collection_is_accepted_as_input_to_a_single_model_cast(): void
    {
        // castToSingleModel() unwrapped only its own Collection and otherwise
        // fell through to get_object_vars(), which for a collection object
        // yields its internal storage rather than its items.
        $dto = new ForeignCollectionHolderDTO([
            'single' => new ForeignCollection(['name' => 'from-collection']),
        ]);

        $this->assertInstanceOf(TagDTO::class, $dto->single);
        $this->assertSame('from-collection', $dto->single->name);
    }

    // -----------------------------------------------------------------
    // The package stays framework-free
    // -----------------------------------------------------------------

    public function test_the_seam_introduces_no_new_dependency(): void
    {
        $manifest = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach (array_keys($manifest['require']) as $package) {
            $this->assertStringStartsNotWith('illuminate/', $package);
            $this->assertStringStartsNotWith('laravel/', $package);
            $this->assertStringStartsNotWith('symfony/', $package);
        }
    }

    public function test_the_core_never_names_a_framework_collection(): void
    {
        $srcDir = dirname(__DIR__, 2).'/src';
        $offenders = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcDir));
        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if (preg_match('/Illuminate\\\\Support\\\\Collection/', file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getFilename();
            }
        }

        $this->assertSame([], $offenders, 'The core must not reference a framework collection.');
    }
}
