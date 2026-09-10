<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\CircularReferenceException;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\FilteringCollection;
use YorCreative\ArgonautDTO\Tests\Fixtures\ForeignCollection;
use YorCreative\ArgonautDTO\Tests\Fixtures\ForeignCollectionCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ForeignCollectionCustomCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ForeignCollectionHolderDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ForeignCollectionPrefixDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\IterableHolderDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\MappingSingleUseCollection;
use YorCreative\ArgonautDTO\Tests\Fixtures\MappingSingleUseCollectionDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NestedCustomCastHolderDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NonTraversableCollectionDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\OverriddenFactoryCustomCastDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\OverriddenFactoryDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ParentWithDifferentChildDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\PropertyBagWithIterator;
use YorCreative\ArgonautDTO\Tests\Fixtures\RedactingIterable;
use YorCreative\ArgonautDTO\Tests\Fixtures\SingleUseCollection;
use YorCreative\ArgonautDTO\Tests\Fixtures\SingleUseCollectionDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\TagDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\UnknownPrefixDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\UnusableCollectionDTO;

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
    // The configured class is recognised as a cast directive prefix
    // -----------------------------------------------------------------

    public function test_the_configured_collection_class_is_accepted_as_a_cast_prefix(): void
    {
        // A caller naming their own collection in the directive --
        // ForeignCollection::class.':'.TagDTO::class -- must be recognised.
        // Only this package's own FQCN and the literal `collection:` were, so
        // any other prefix matched nothing, fell through to class_exists() and
        // returned the value uncast, with no error.
        $dto = new ForeignCollectionPrefixDTO(['tags' => [['name' => 'a'], ['name' => 'b']]]);

        $this->assertInstanceOf(ForeignCollection::class, $dto->tags);
        $this->assertContainsOnlyInstancesOf(TagDTO::class, $dto->tags->all());
        $this->assertCount(2, $dto->tags->all());
    }

    public function test_the_packages_own_prefix_still_works_on_a_reconfigured_dto(): void
    {
        // Both prefixes are recognised, so a DTO that changed its collection
        // class can still be handed a directive written the original way.
        $dto = new ForeignCollectionCastDTO(['tags' => [['name' => 'a']]]);

        $this->assertInstanceOf(ForeignCollection::class, $dto->tags);
        $this->assertSame([['name' => 'a']], $dto->toArray()['tags']);
    }

    public function test_an_unrecognised_prefix_is_still_left_alone(): void
    {
        // Guard against the prefix check becoming permissive: a directive
        // naming something that is not the configured collection must not be
        // treated as one.
        $dto = new UnknownPrefixDTO(['tags' => [['name' => 'a']]]);

        $this->assertIsArray($dto->tags, 'An unknown directive leaves the value as given.');
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
            [['name' => 'a'], ['name' => 'b']],
            $dto->toArray()['tags']
        );
    }

    public function test_a_foreign_collection_round_trips_through_json(): void
    {
        $dto = new ForeignCollectionCastDTO(['tags' => [['name' => 'a']]]);

        $this->assertStringContainsString('"tags":[{"name":"a"}]', $dto->toJson());
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

    public function test_a_configured_collection_is_unwrapped_by_a_single_model_cast(): void
    {
        // castToSingleModel() unwrapped only its own Collection and otherwise
        // fell through to get_object_vars(), which for a collection object
        // yields its internal storage rather than its items.
        $dto = new ForeignCollectionCastDTO([
            'single' => new ForeignCollection(['name' => 'from-collection']),
        ]);

        $this->assertInstanceOf(TagDTO::class, $dto->single);
        $this->assertSame('from-collection', $dto->single->name);
    }

    public function test_an_unconfigured_iterable_object_is_still_read_as_a_property_bag(): void
    {
        // The DTO below has NOT named ForeignCollection, so the value is just an
        // object that happens to be iterable. Its properties are what a
        // single-model cast wants -- reading its iterator instead would replace
        // the property bag with unrelated entries.
        $dto = new ForeignCollectionHolderDTO([
            'single' => new PropertyBagWithIterator,
        ]);

        $this->assertInstanceOf(TagDTO::class, $dto->single);
        $this->assertSame('property-name', $dto->single->name);
    }

    // -----------------------------------------------------------------
    // Misconfiguration is reported, not discovered later
    // -----------------------------------------------------------------

    public function test_a_collection_without_a_map_method_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/map\(\)/');

        new UnusableCollectionDTO(['tags' => [['name' => 'a']]]);
    }

    public function test_a_non_traversable_collection_is_rejected(): void
    {
        // It maps, so casting would appear to work -- and then the value would
        // land in serialized output as a raw object.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Traversable/');

        new NonTraversableCollectionDTO(['tags' => [['name' => 'a']]]);
    }

    // -----------------------------------------------------------------
    // Interactions
    // -----------------------------------------------------------------

    public function test_parent_and_child_may_use_different_collection_classes(): void
    {
        $dto = new ParentWithDifferentChildDTO([
            'children' => [
                ['tags' => [['name' => 'a']]],
                ['tags' => [['name' => 'b']]],
            ],
        ]);

        $this->assertInstanceOf(ForeignCollection::class, $dto->children, 'parent uses its own');
        $this->assertInstanceOf(
            Collection::class,
            $dto->children->all()[0]->tags,
            'the child builds with its own, not the parent\'s'
        );

        $this->assertSame(
            [['name' => 'a']],
            $dto->toArray()['children'][0]['tags'],
            'both levels serialize through their own class'
        );
    }

    public function test_a_custom_cast_collection_nested_inside_a_dto_serializes(): void
    {
        // Actually nested: the outer DTO holds a collection of inner DTOs, each
        // of which holds a custom-cast collection of its own.
        $dto = new NestedCustomCastHolderDTO([
            'inner' => [
                ['tags' => ['a', 'b']],
                ['tags' => ['c']],
            ],
        ]);

        $this->assertInstanceOf(ForeignCollection::class, $dto->inner);
        $this->assertInstanceOf(ForeignCollection::class, $dto->inner->all()[0]->tags);

        $this->assertSame(
            [['tags' => ['A', 'B']], ['tags' => ['C']]],
            $dto->toArray()['inner']
        );
    }

    public function test_with_preserves_the_configured_collection(): void
    {
        $dto = new ForeignCollectionCastDTO(['tags' => [['name' => 'a']]]);
        $copy = $dto->with(['tags' => [['name' => 'b'], ['name' => 'c']]]);

        $this->assertInstanceOf(ForeignCollection::class, $copy->tags);
        $this->assertCount(2, $copy->tags->all());
        $this->assertCount(1, $dto->tags->all(), 'the original is untouched');
    }

    // -----------------------------------------------------------------
    // A collection cycle is caught
    // -----------------------------------------------------------------

    public function test_a_collection_holding_itself_is_reported(): void
    {
        // Walking a collection does not spend depth, and the guard on toArray()
        // tracks DTOs, so a self-referencing collection would recurse forever.
        $collection = new Collection;
        $collection['self'] = $collection;

        $this->expectException(CircularReferenceException::class);

        (new IterableHolderDTO(['payload' => $collection]))->toArray();
    }

    public function test_mutually_referencing_collections_are_reported(): void
    {
        $a = new Collection;
        $b = new Collection;
        $a['b'] = $b;
        $b['a'] = $a;

        $this->expectException(CircularReferenceException::class);

        (new IterableHolderDTO(['payload' => $a]))->toArray();
    }

    public function test_the_same_collection_referenced_twice_is_not_a_cycle(): void
    {
        // The guard is scoped to the active path, so a collection appearing in
        // two sibling positions serializes twice rather than being mistaken for
        // a cycle.
        $shared = new Collection(['x']);

        $dto = new IterableHolderDTO(['payload' => new Collection([$shared, $shared])]);

        $this->assertSame([['x'], ['x']], $dto->toArray()['payload']);
    }

    public function test_the_same_collection_serializes_after_its_cycle_is_repaired(): void
    {
        // Deliberately the SAME collection object throughout: replacing it with
        // a fresh one could not detect a guard entry left behind, because the
        // leak is keyed on the object that failed.
        $collection = new Collection;
        $collection['self'] = $collection;
        $holder = new IterableHolderDTO(['payload' => $collection]);

        try {
            $holder->toArray();
            $this->fail('The cycle should have been reported.');
        } catch (CircularReferenceException) {
            $this->addToAssertionCount(1);
        }

        unset($collection['self']);
        $collection['ok'] = 'value';

        $this->assertSame(
            ['ok' => 'value'],
            $holder->toArray()['payload'],
            'A guard entry left behind would report this same object as a cycle again.'
        );
    }

    // -----------------------------------------------------------------
    // Objects that merely happen to be iterable are left alone
    // -----------------------------------------------------------------

    public function test_a_json_serializable_iterable_keeps_its_own_representation(): void
    {
        // Walking it would publish the iterated contents and bypass whatever
        // jsonSerialize() deliberately exposes -- a redaction, for instance.
        $dto = new IterableHolderDTO(['payload' => new RedactingIterable]);

        $this->assertStringContainsString('[REDACTED]', $dto->toJson());
        $this->assertStringNotContainsString('secret', $dto->toJson());
    }

    public function test_a_generator_is_not_consumed_by_serialization(): void
    {
        $dto = new IterableHolderDTO(['payload' => (function () {
            yield 'a';
        })()]);

        $dto->toArray();
        $dto->toJson();

        $this->addToAssertionCount(1); // reaching here without throwing is the assertion
    }

    // -----------------------------------------------------------------
    // Serialization iterates; it does not read all()
    // -----------------------------------------------------------------

    public function test_a_subclass_overriding_get_iterator_decides_what_is_emitted(): void
    {
        // all() would publish everything the collection stores. A subclass that
        // narrows its iterator is narrowing what it publishes, and that is what
        // serialization has to honour.
        $collection = new FilteringCollection(['public' => 'ok', 'secret' => 'hidden']);

        $output = (new IterableHolderDTO(['payload' => $collection]))->toArray()['payload'];

        $this->assertSame(['public' => 'ok'], $output);
        $this->assertArrayNotHasKey('secret', $output);
    }

    // -----------------------------------------------------------------
    // A cycle is detected before the collection is read
    // -----------------------------------------------------------------

    public function test_a_cycle_in_a_single_use_collection_is_reported_as_one(): void
    {
        // Reading the collection to find its items would consume the generator,
        // so re-entry would surface as a closed-generator error instead of the
        // cycle it actually is.
        $holder = null;
        $collection = new SingleUseCollection(function () use (&$holder) {
            yield 'self' => $holder;
        });
        $holder = $collection;

        $this->expectException(CircularReferenceException::class);

        (new SingleUseCollectionDTO(['payload' => $collection]))->toArray();
    }

    // -----------------------------------------------------------------
    // A single-use collection that really maps
    // -----------------------------------------------------------------

    public function test_a_single_use_collection_maps_items_into_dtos(): void
    {
        // The cycle fixture's map() returns $this, which is enough to be
        // recognised but never exercises mapping. This one transforms, over a
        // generator that can be traversed only once.
        $dto = new MappingSingleUseCollectionDTO([
            'tags' => ['first' => ['name' => 'a'], 'second' => ['name' => 'b']],
        ]);

        $this->assertInstanceOf(MappingSingleUseCollection::class, $dto->tags);

        $items = iterator_to_array($dto->tags);

        $this->assertContainsOnlyInstancesOf(TagDTO::class, $items);
        $this->assertSame(
            ['first', 'second'],
            array_keys($items),
            'Mapping a keyed collection must not reindex it.'
        );
        $this->assertSame('a', $items['first']->name);
        $this->assertSame('b', $items['second']->name);
    }

    public function test_a_single_use_collection_serializes_after_mapping(): void
    {
        // The cast already traversed the source generator once; serialization
        // walks the mapped collection, which is a separate one.
        $dto = new MappingSingleUseCollectionDTO([
            'tags' => ['first' => ['name' => 'a']],
        ]);

        $this->assertSame(
            ['first' => ['name' => 'a']],
            $dto->toArray()['tags']
        );
    }

    // -----------------------------------------------------------------
    // Validation survives an overridden factory
    // -----------------------------------------------------------------

    public function test_an_overridden_factory_is_still_validated(): void
    {
        // The checks live outside newCollection(), so replacing the factory
        // does not skip them.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Traversable/');

        new OverriddenFactoryDTO(['tags' => [['name' => 'a']]]);
    }

    public function test_an_overridden_factory_is_validated_on_the_custom_cast_path_too(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Traversable/');

        new OverriddenFactoryCustomCastDTO(['tags' => ['a']]);
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
