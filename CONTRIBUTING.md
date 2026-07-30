# Contributing

Contributions are welcome.

## Before opening a pull request

- Reproduce the issue or confirm the improvement is not already covered.
- Check open issues and pull requests for existing work.
- Keep changes focused on one fix or feature.

## Local checks

Run the same checks the release process depends on:

```bash
composer install
composer test
composer phpstan
composer lint:test
composer validate --strict
```

## Expectations

- Add or update tests for behavior changes.
- Update `README.md` for user-facing API or workflow changes.
- Keep public API changes semver-safe unless the release explicitly targets a new major version.
