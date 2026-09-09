# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Targeting 1.1.0. Four further features are in progress and will land in this
entry before it is tagged: `with()`, custom cast classes, key mapping, and
bulk validation.

### Added

- Attribute-based casting: `#[CastTo]`, `#[CastCollection]` and `#[CastEnum]`
  property attributes as an alternative to the `$casts` array. Where both
  describe the same property, the `$casts` array takes precedence.
- `Collection` is now generic (`Collection<TValue>`), so static analysis
  narrows element types.
- New `Collection` methods: `reduce`, `last`, `contains`, `pluck`, `groupBy`,
  `keyBy`.
- Named constructors on both `ArgonautDTO` and `ArgonautImmutableDTO`:
  `fromArray()` and `fromJson()`. `fromJson()` throws `JsonException` both for
  malformed JSON and for valid JSON that does not decode to an object.

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
with one of these names and a different signature, you will get a fatal error.
Grep your DTOs for `fromArray` and `fromJson`, and your `Collection`
subclasses for `reduce`, `last`, `contains`, `pluck`, `groupBy`, `keyBy`.

## [1.0.0] - 2026-07-30

### Added

- Initial release: `ArgonautDTO`, `ArgonautImmutableDTO`, `ArgonautAssembler`,
  `Collection`, nested DTO and backed-enum casting, recursive and
  depth-limited serialization, circular-reference detection, and
  convention-based validation.

[Unreleased]: https://github.com/YorCreative/Argonaut-DTO/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/YorCreative/Argonaut-DTO/releases/tag/v1.0.0
