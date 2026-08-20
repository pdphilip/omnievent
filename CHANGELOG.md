# Changelog

All notable changes to `omnievent` will be documented in this file.

## v3.2.0 - 2026-04-06

This release is compatible with Laravel 11, 12 & 13

### Added

- **Laravel 13 support**
- Composer test scripts: `composer test:l11`, `composer test:l12`, `composer test:l13`, `composer test:all`

### Fixed

- **L13 boot compatibility** - `Eventable::bootEventable()` refactored to use class string instead of `new static` to avoid L13's `LogicException` on model instantiation during boot

### Changed

- Dropped Laravel 10 support (EOL)
- PHP minimum bumped from 8.2 to 8.3
- `pdphilip/elasticsearch` bumped to `^5.6`
- `pdphilip/omniterm` bumped to `^3.0`
- `pdphilip/cf-request` bumped to `^3.1`
- CI matrix updated: PHP 8.3/8.4, Laravel 11/12/13
- PHPStan config: removed `config` path (larastan 3 false positive), regenerated baseline

**Full Changelog**: https://github.com/pdphilip/omnievent/compare/v3.1.0...v3.2.0

## v3.1.0

### Added

- `triggerEvent()` and `eventSearch()` now accept `\BackedEnum` values in addition to strings. Both string-backed and int-backed enums are supported - the value is extracted and cast to string automatically.

### Tests

- Added `EventableEnumTest` covering enum and string inputs for `triggerEvent()` and `eventSearch()`.

## v3.0.0

### Breaking Changes

- **Removed chainable query methods** from the `Eventable` trait: `eventFrom()`, `eventTo()`, `whereType()`, `modelOnly()`, `getEvents()`, `distinctEvents()`, `paginateDistinctEvents()`, `countEvents()`, `paginateEvents()`. Use
  `viaEvents()` with standard Eloquent builder methods instead.
- **`viaEvents()`** now returns an Elasticsearch Eloquent `Builder` directly (was returning the model instance for chaining).
- **Removed `validateEventModel()`**, `validateConnection()`, `validateAndContinue()` from the `Eventable` trait. Schema validation now happens once on boot.
- **Removed `transformModelRelationship()`** from `EventModel`.
- **Removed `Timer` trait** (`src/Traits/Timer.php` deleted).
- **Removed `getDetails()`** from `EventModelException`.
- **Config:** Removed `queue` key (was unused).

### Added

- `eventSearch(string $event)` - shorthand returning a `Collection` of matching events.
- `asModel()` on `EventModel` - resolves the base model directly.
- Request capture via DI container (`app(CfRequest::class)`) - graceful in non-HTTP contexts (queues, CLI).
- Full test suite: unit, integration, and feature tests.

### Changed

- `Eventable` trait rewritten: 4 public methods (`triggerEvent`, `viaEvents`, `eventSearch`, `bootEventable`) replacing 15+.
- `EventModel::guessBaseModelName()` uses `config('omnievent.namespaces.models')` instead of hardcoded `App\Models`.
- `EventModel::validateSchema()` uses `Schema::connection()` for explicit connection context and `hasTable()` for index existence checks.
- `EventModel::deleteAllEvents()` uses `->get()->each->delete()` for ES compatibility.
- `EventModel` connection set from `config('omnievent.database')` in constructor.
- `EventModelException` constructor simplified; `$previous` parameter now optional.
- `OmniEventMakeCommand` uses config-based namespaces for both model and event model resolution.
- Event stub uses template variables for namespace and model import.
- CI workflow: added Elasticsearch service container, Laravel 12 / PHP 8.4 support, dropped Windows matrix.
- Requires `pdphilip/omniterm: ^2`, `pdphilip/cf-request: ^3`.

## v2.1.0

- OmniTerm v2 compatibility.

## v2.0.0

- Laravel 11 support.

## v1.0.0

- Initial release.
