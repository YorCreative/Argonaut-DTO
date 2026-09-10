# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Targeting 1.1.0. The scope for this release is complete and it is awaiting
release.

### Added

- Custom cast classes: implement `CastsArgonautAttribute` and declare it either
  as `protected array $casts = ['price' => MoneyCast::class]` or as
  `#[CastWith(MoneyCast::class)]`. Implementations must be stateless — one
  instance per cast class is shared.
- Attribute-based casting: `#[CastTo]`, `#[CastCollection]` and `#[CastEnum]`
  property attributes as an alternative to the `$casts` array. Where both
  describe the same property, the `$casts` array takes precedence.
- `Collection` is now generic (`Collection<TValue>`), so static analysis
  narrows element types.
- New `Collection` methods: `reduce`, `last`, `contains`, `pluck`, `groupBy`,
  `keyBy`.
- Named constructors on both `ArgonautDTO` and `ArgonautImmutableDTO`:
  `fromArray()` and `fromJson()`. `fromJson()` throws `JsonException` both for
  malformed JSON and for valid JSON that does not decode to an object — this
  includes a JSON array such as `[{"fullName":"Ada"}]`, `[1,2]` or `[]`. The
  root type is read from the document rather than inferred from the decoded
  value, so an empty array is correctly rejected and a JSON object with
  numeric keys (`{"0":"a"}`) is correctly accepted; associative decoding makes
  both of those indistinguishable by shape alone.
- `Collection::validateAll()` and `Collection::isValidAll()` for validating a
  collection of DTOs in one call, with errors keyed by the item that failed.
- `with()` on both `ArgonautDTO` and `ArgonautImmutableDTO`, returning a copy
  with the given attributes applied. Shallow copy: nested objects are shared.
- Key mapping: incoming keys can be renamed to property names, either as
  `protected array $maps = ['first_name' => 'firstName'];` or as a
  `#[MapFrom('first_name')]` attribute on the property. Where both describe
  the same target property, `$maps` wins and the attribute for that property
  is dropped entirely, even when the two forms name different incoming keys.
  Mapping runs before anything else that processes input — before
  `$prioritizedAttributes` and before casting — so a `$casts` entry for a
  mapped property is keyed by the property name, not the incoming key. New
  `toMappedArray()` and `toMappedJson()` methods reverse the mapping on
  output; `toArray()` and `toJson()` are unchanged. Key mapping is top-level
  only: it does not rename keys inside nested DTOs, because by the time
  `toArray()` runs, nested DTOs have already been flattened into plain
  arrays.

### Behavior on misconfiguration

Six paths added in this release could fail silently — losing a value, doing
nothing, or reporting a value indistinguishable from a typo. Each now reports
the problem at the point it is detectable:

- `toMappedArray()` / `toMappedJson()` throw `LogicException` when the inverse
  mapping lands two keys on the same output key, instead of dropping one value.
  `toArray()` and `toJson()` are unaffected.
- Declaring `#[MapFrom]` for the same incoming key on two properties throws
  `LogicException`. Previously whichever property reflection reached last won
  and the other was never populated.
- `setMappedAttribute()` is the single-attribute entry point for an incoming
  alias: it resolves the key through `$maps`/`#[MapFrom]` and hands the property
  name to `setAttribute()`. `setAttribute()` itself keeps its 1.0.0 contract and
  takes property names only, so an existing override needs no change and is
  still called exactly once per assignment, under the name it declared.
- `Collection::contains()` now treats any callable except a string as a
  predicate — `[$object, 'method']` and `__invoke` objects included. A string
  remains a value, so a collection of strings stays searchable. Predicates are
  invoked as `($item, $key)`.
- `Collection::pluck()` throws `LogicException` for a property that exists but
  is not public, rather than yielding `null`. An absent key still yields `null`.
- `Collection::validateAll(true)` builds its `ValidationException` from the
  errors it already collected, instead of re-validating the failing item. Any
  work or side effect in `rules()` now happens once per item rather than twice
  for the first failure.

### Fixed

- `with()` on `ArgonautImmutableDTO` preserves private properties declared by
  a subclass. The copy was built from `get_object_vars($this)`, which resolves
  in the parent's scope and cannot see them, so such a property reverted to its
  declared default on the copy — or, for a typed property with no default, was
  left uninitialized and fatal on first read.
- An internal key (`casts`, `nestedAssemblers`, `maps`, ...) passed to `with()`
  no longer blanks that configuration on the copy. It counted as a changed
  property, so it was not carried over, while `initializeFromAttributes()`
  skipped it as internal and never set it.
- A numeric incoming alias such as `['123' => 'code']` survives the map merge.
  `array_merge()` renumbers integer keys, and PHP stores a numeric-string key
  as an integer, so the alias was silently reindexed and stopped matching.
- Bulk custom casts skip `null` elements in both the array and
  `collection:` forms, mirroring the null guard already applied to a whole
  value. Every cast implementation previously had to null-check for itself.
- `Collection::pluck()` reads a property published through `__isset()`/`__get()`
  again; the accessibility guard added in this release ran before the magic
  accessors could answer.
- An overridden `setAttribute()` is called for every input path —
  constructor, `setAttributes()`, `merge()` and `with()` — and always receives
  the canonical property name. Bulk input is mapped once, before dispatch, so
  nothing an override does from inside an assignment can be mistaken for part
  of the surrounding operation.
- `ArgonautImmutableDTO` can be subclassed when a parent declares `readonly`
  properties. Initialization went through `ReflectionProperty::setValue()`,
  which carries the caller's scope; PHP 8.3 permits a readonly property to be
  initialized only from the scope that declares it, so this raised
  `Cannot initialize readonly property ... from scope ...` there while working
  on 8.4+, which relaxed the rule. Assignment is now bound to the declaring
  class, which also resolves to the correct slot when a parent and a child both
  declare a private property of the same name.

### Changed (no behavior change)

- `collection()` now declares `@return Collection<static>`, so static analysis
  narrows its elements. The method itself is untouched and behaves exactly as
  in 1.0.0.

### Changed

- CI audits `composer.lock` before installing dependencies, and the
  dependency-review job now runs `actions/dependency-review-action`.
- Development dependencies: `laravel/pint` 1.31.1, `phpstan/phpstan` 2.2.13.
- **`getExcludedSerializationProperties()` now also excludes `maps`, so `maps`
  is a reserved property name on both base classes.** This is a genuine
  runtime behavior change for two kinds of existing code, not merely a static
  one:
  - If one of your v1.0.0 DTOs already declares its own `public`/`protected
    array $maps`, that property now silently stops appearing in `toArray()`
    and `toJson()` output — no error, no warning, just a missing key. (A
    non-`array` `$maps` on an existing DTO is also newly a fatal type error,
    since the trait declares `protected array $maps = [];`.)
  - If you override `getExcludedSerializationProperties()` by copying the
    v1.0.0 list and appending your own excluded names, `'maps'` is not in
    your copy, so it now leaks into every serialized payload as `"maps":[]`
    (or whatever you have set it to). Call
    `parent::getExcludedSerializationProperties()` and merge into it, rather
    than hard-coding the list, to avoid this.

### Upgrading from 1.0.0

No public method was renamed, removed, or had its signature changed. One
runtime behavior *did* change for existing code — see the `maps` bullet
under **Changed** above — so do not read this release as behavior-neutral
without checking that first.

One thing to check before upgrading: this release adds methods and one
property to classes you subclass. PHP enforces signature compatibility on
inherited methods, including static ones, so if one of your DTOs or
collections already declares a method with one of these names, you may get a
fatal error. **The dominant failure mode is the return type, not the
parameter list** — comparing parameter lists alone will tell you nothing is
wrong when it is. Concretely:

- `static function fromArray(array $data): self`, `: YourDTO`, or with no
  return type at all — **fatal**. Only `: static` (optionally with an added
  optional parameter) is compatible. `: self` is arguably the most common
  named-constructor idiom in PHP DTO code, so check it specifically.
- A non-static override of `fromArray()`/`fromJson()` — **fatal**
  ("Cannot make static method non static").
- `Collection::last()` and `Collection::keyBy()` are the riskiest names to
  already have overridden: this library's `last()` takes a *default* value
  first, and `keyBy()` takes a *callable*, both unlike
  `Illuminate\Support\Collection`. A Laravel-shaped `last(?callable $callback
  = null, $default = null)` or a string-keyed `keyBy(string $column)` is
  **fatal**.
- **Every one of `reduce`, `last`, `contains`, `pluck`, `groupBy` and
  `keyBy` is fatal if your override omits a return type — which is how
  `Illuminate\Support\Collection` declares all of them.** This library types
  `pluck`/`groupBy`/`keyBy` as `: static` and `reduce`/`contains` as
  `: mixed`/`: bool`; a child method with no return type at all is always a
  widening and always fatal, regardless of what its parameters look like. Do
  not assume `contains`, `pluck`, `reduce` or `groupBy` are safe just because
  their parameter lists happen to match — check the return type on every
  hit, not just `last`/`keyBy`.
- `setMappedAttribute()` is a new `public` method on `ArgonautDTO`, declared
  as `setMappedAttribute(string $key, mixed $value): static`. A pre-existing
  method of that name is a collision on the same terms as the others above —
  check the return type and staticness, not only the parameters.
- `$maps` is a new `protected array` property on both base classes (see the
  **Changed** entry above) — check for a pre-existing `$maps` property the
  same way you would check for a method collision, since a type mismatch is a
  fatal and a type match silently loses data.
- Three new `protected` methods are also part of the collision surface, in
  addition to the public ones above: `encodeJson()` (declared as
  `encodeJson(array $data, int $options): string`; a same-named method with a
  different signature is **fatal** — verified against a DTO that already
  owned custom JSON encoding), `keyMaps()`, and `mapInputKeys()`.
- Check any **trait** your DTOs or `Collection` subclasses `use`, too — a
  trait method is checked against the inherited signature identically to a
  method declared directly on the class.

Grep your DTOs for `fromArray`, `fromJson`, `with`, `setMappedAttribute`,
`toMappedArray`, `toMappedJson`, `maps`, `encodeJson`, `keyMaps`, and
`mapInputKeys`, and your
`Collection` subclasses for `reduce`, `last`, `contains`, `pluck`, `groupBy`,
`keyBy`, `validateAll`, `isValidAll` — then check each hit's return type and
staticness, not just its parameters.

`Collection` is now a generic class (`Collection<TValue>`). This is a
static-analysis improvement with no runtime effect, but if you run PHPStan
at level 6 or higher, any bare `Collection` property or return type you
declare (e.g. `public Collection $tags;`) will newly report
`missingType.generics`, because the type parameter is unspecified. This is
the feature working as intended, not a regression: resolve it either by
adding the type parameter — `/** @var Collection<TagDTO> */` above the
property — or, if you would rather defer that work, by adding
`missingType.generics` to your `phpstan.neon` `ignoreErrors`.

## [1.0.0] - 2026-07-30

### Added

- Initial release: `ArgonautDTO`, `ArgonautImmutableDTO`, `ArgonautAssembler`,
  `Collection`, nested DTO and backed-enum casting, recursive and
  depth-limited serialization, circular-reference detection, and
  convention-based validation.

[Unreleased]: https://github.com/YorCreative/Argonaut-DTO/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/YorCreative/Argonaut-DTO/releases/tag/v1.0.0
