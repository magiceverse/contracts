# Changelog

All notable changes to this package are documented here. The package follows
[Semantic Versioning](https://semver.org/); each schema also carries its own
version (see `Version`).

## [0.1.0] - Unreleased

### Added
- JSON Schemas (draft 2020-12) for Technique, PrintPosition, Product, the delta
  feed page and the CloudEvents webhook envelope, all at 1.0.0, plus shared
  definitions in `common/v1`.
- PHP DTOs on spatie/laravel-data with backed enums for every schema enum.
- `Schema` for offline validation with every error reported by JSON pointer
  (`ContractViolation`), and `Version` as the single source of contract versions.
- Cross-system fixtures, valid and invalid, for every entity.
- Price attribute values (`{"EUR": "12.00"}`), CloudEvents `dataschema` and
  `traceparent` attributes.
- DTOs leave empty maps out of `toArray()` so they never encode as `[]`.
