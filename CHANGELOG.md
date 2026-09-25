# Changelog

All notable changes to this package are documented here. The package follows
[Semantic Versioning](https://semver.org/); each schema also carries its own
version (see `Version`).

## [0.1.1] - 2026-09-25

### Added
- Product 1.1.0: attribute values may be a UnoPim measurement,
  `{"value": "0.35", "unit": "KILOGRAM"}` (value a decimal string or number,
  closed object). An object with a `value` key is checked as a measurement,
  any other object as a price.
- Product 1.1.0: media items take optional, nullable `locale` and `channel`, so
  images of locale- or channel-specific attributes keep their scope. `MediaData`
  has both properties.

## [0.1.0] - 2026-09-25

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
