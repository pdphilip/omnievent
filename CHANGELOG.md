# Changelog

All notable changes to `omnievent` will be documented in this file.

## v3.0.0

### Breaking Changes

- **Removed chainable query methods** from the `Eventable` trait: `eventFrom()`, `eventTo()`, `whereType()`, `modelOnly()`, `getEvents()`, `distinctEvents()`, `paginateDistinctEvents()`, `countEvents()`, `paginateEvents()`. Use `viaEvents()` with standard Eloquent builder methods instead.
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
