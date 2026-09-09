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
  includes a JSON array such as `[{"fullName":"Ada"}]` or `[1,2]`. An empty
  array (`[]`) is indistinguishable from an empty object (`{}`) after
  decoding, so both are accepted and produce an empty DTO.
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

### Changed (no behavior change)

- `collection()` now declares `@return Collection<static>`, so static analysis
  narrows its elements. The method itself is untouched and behaves exactly as
  in 1.0.0.

### Changed

- CI audits `composer.lock` before installing dependencies, and the
  dependency-review job now runs `actions/dependency-review-action`.
- Development dependencies: `laravel/pint` 1.31.1, `phpstan/phpstan` 2.2.13.

### Upgrading from 1.0.0

No public method was renamed, removed, or had its signature changed, and no
runtime behavior changed for existing code.

One thing to check before upgrading: this release adds methods to classes you
subclass. PHP enforces signature compatibility on inherited methods, including
static ones, so if one of your DTOs or collections already declares a method
with one of these names, you may get a fatal error. **The dominant failure
mode is the return type, not the parameter list** — comparing parameter
lists alone will tell you nothing is wrong when it is. Concretely:

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
  **fatal**. Laravel-shaped `contains`, `pluck`, `reduce` and `groupBy`
  overrides are compatible.
- Check any **trait** your DTOs or `Collection` subclasses `use`, too — a
  trait method is checked against the inherited signature identically to a
  method declared directly on the class.

Grep your DTOs for `fromArray`, `fromJson`, `with`, `toMappedArray`, and
`toMappedJson`, and your `Collection` subclasses for `reduce`, `last`,
`contains`, `pluck`, `groupBy`, `keyBy`, `validateAll`, `isValidAll` — then
check each hit's return type and staticness, not just its parameters.

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
