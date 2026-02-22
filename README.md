<div align="center">

# OmniEvent for Laravel

<img src="https://cdn.snipform.io/pdphilip/omnievent/omnievent-logo.png" width="500" alt="OmniEvent for Laravel" />

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pdphilip/omnievent.svg?style=flat-square)](https://packagist.org/packages/pdphilip/omnievent)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/omnievent/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pdphilip/omnievent/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/omnievent/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/pdphilip/omnievent/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/pdphilip/omnievent.svg?style=flat-square)](https://packagist.org/packages/pdphilip/omnievent)

Track model events in Elasticsearch. Query them with Eloquent.

</div>

```php
$user->triggerEvent('login', ['ip' => $request->ip()]);
$user->triggerEvent('purchase', ['amount' => 99.95, 'currency' => 'USD']);
$user->triggerEvent('password_reset');
```

Every event is stored as a document in Elasticsearch with a timestamp, optional metadata, and automatic request context (IP, browser, device, geolocation) via [CfRequest](https://github.com/pdphilip/cf-request).

Query events using the full Elasticsearch Eloquent builder - the same API you already know from [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch):

```php
// Quick search - returns Collection
$logins = User::eventSearch('login');

// Full power - Elasticsearch Eloquent builder
User::viaEvents()
    ->where('event', 'purchase')
    ->where('meta.amount', '>=', 50)
    ->where('created_at', '>=', now()->subDays(30))
    ->orderByDesc('created_at')
    ->paginate(20);
```

Events are automatically cleaned up when a model is deleted.

---

## Requirements

| | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 10 / 11 / 12 |
| Elasticsearch | 8.x |

## Installation

```bash
composer require pdphilip/omnievent
```

```bash
php artisan omnievent:install
```

This publishes `config/omnievent.php`.

> Requires a [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) connection. See [configuration guide](https://elasticsearch.pdphilip.com/getting-started/configuration).

---

## Quick Start

### 1. Generate an event model

```bash
php artisan omnievent:make User
```

This creates `app/Models/Events/UserEvent.php`:

```php
namespace App\Models\Events;

use App\Models\User;
use PDPhilip\OmniEvent\EventModel;

class UserEvent extends EventModel
{
    protected $baseModel = User::class;

    public function modelType(User $model): ?string
    {
        return null;
    }
}
```

### 2. Add the trait

```php
use PDPhilip\OmniEvent\Eventable;

class User extends Model
{
    use Eventable;
}
```

That's it. The Elasticsearch index is created automatically on first boot.

### 3. Track events

```php
$user->triggerEvent('login');
$user->triggerEvent('purchase', ['amount' => 49.99, 'currency' => 'EUR']);
```

---

## Querying Events

### Quick Search

Returns a `Collection` of event records matching the event name:

```php
$logins = User::eventSearch('login');
```

### Full Builder

Returns the Elasticsearch Eloquent builder for the event model. Chain any query method you'd use on a normal Eloquent model:

```php
// Filter by event type and date range
User::viaEvents()
    ->where('event', 'purchase')
    ->where('created_at', '>=', now()->subMonth())
    ->get();

// Paginate
User::viaEvents()
    ->where('event', 'login')
    ->orderByDesc('created_at')
    ->paginate(15);

// Count
User::viaEvents()
    ->where('event', 'purchase')
    ->count();

// Filter by metadata
User::viaEvents()
    ->where('meta.currency', 'USD')
    ->where('meta.amount', '>=', 100)
    ->get();

// Filter by request context
User::viaEvents()
    ->where('request.country', 'US')
    ->where('request.deviceType', 'mobile')
    ->get();
```

Since `viaEvents()` returns a standard Elasticsearch Eloquent builder, you get access to everything: `where`, `orderBy`, `limit`, `paginate`, `count`, `aggregate`, `searchTerm`, `distinct`, and more. See the [Laravel-Elasticsearch docs](https://elasticsearch.pdphilip.com) for the full query API.

---

## Event Record Structure

Each event is stored as an Elasticsearch document with these fields:

| Field | Type | Description |
|---|---|---|
| `model_id` | keyword | The base model's primary key |
| `model_type` | keyword | Optional type from `modelType()` method |
| `event` | keyword | The event name |
| `ts` | integer | Unix timestamp |
| `meta` | flattened | Custom metadata (any key-value data) |
| `created_at` | datetime | Laravel timestamp |

When `save_request` is enabled in config, each event also captures:

| Field | Type | Description |
|---|---|---|
| `request.ip` | keyword | Client IP address |
| `request.browser` | keyword | Browser name and version |
| `request.device` | keyword | Device brand and model |
| `request.deviceType` | keyword | `desktop`, `mobile`, `tablet`, `tv` |
| `request.os` | keyword | Operating system |
| `request.country` | keyword | ISO country code |
| `request.region` | keyword | Region/state |
| `request.city` | keyword | City |
| `request.postal_code` | keyword | Postal code |
| `request.lat` | float | Latitude |
| `request.lon` | float | Longitude |
| `request.timezone` | keyword | Timezone |
| `request.is_bot` | boolean | Bot detection flag |
| `request.geo` | geo_point | GeoJSON point for geo queries |

Request data is captured automatically via [CfRequest](https://github.com/pdphilip/cf-request). Behind Cloudflare, you get full geolocation. Without Cloudflare, you still get IP, browser, device, and bot detection.

---

## Model Types

The `modelType()` method on your event model lets you tag events with a model subtype. Useful for segmenting events by user role, account tier, or any model attribute:

```php
class UserEvent extends EventModel
{
    protected $baseModel = User::class;

    public function modelType(User $model): ?string
    {
        return $model->plan; // 'free', 'pro', 'enterprise'
    }
}
```

Then filter by type:

```php
User::viaEvents()
    ->where('model_type', 'pro')
    ->where('event', 'export')
    ->count();
```

Return `null` to skip the type field entirely.

---

## Resolving the Base Model

Every event record can resolve back to its source model:

```php
$event = UserEvent::where('event', 'login')->first();

// Via relationship (eager-loadable)
$event->model;          // User instance
$event->model->name;    // 'David'

// Via direct lookup
$event->asModel();      // User instance or null

// Eager load across a collection
$events = UserEvent::where('event', 'login')
    ->with('model')
    ->get();
```

The base model is resolved from the `$baseModel` property on your event model. If not set, OmniEvent guesses it from the table name (`user_events` -> `User`).

---

## Automatic Cleanup

When a model is deleted, all its events are automatically removed:

```php
$user->delete(); // All UserEvent records for this user are deleted
```

This is registered via the `Eventable` trait's boot method. No manual cleanup needed.

---

## Configuration

```php
// config/omnievent.php
return [
    // Elasticsearch connection name
    'database' => 'elasticsearch',

    // Throw exceptions on event save failure (false = log and return false)
    'throw_exceptions' => true,

    // Capture request metadata (IP, browser, geo) with each event
    'save_request' => true,

    // Namespaces for model resolution
    'namespaces' => [
        'models' => 'App\Models',
        'events' => 'App\Models\Events',
    ],

    // File paths (relative to app/) for event model discovery
    'app_paths' => [
        'models' => 'Models/',
        'events' => 'Models/Events/',
    ],
];
```

### Error Handling

By default, exceptions from Elasticsearch are thrown. Set `throw_exceptions` to `false` to silently log errors and return `false` from `triggerEvent()`:

```php
// config/omnievent.php
'throw_exceptions' => false,
```

```php
// Now returns false instead of throwing
$success = $user->triggerEvent('login'); // false if ES is down
```

This is useful in production where event tracking should never break your application flow.

---

## API Reference

### Eventable Trait (on your base model)

| Method | Returns | Description |
|---|---|---|
| `$model->triggerEvent(string $event, array $meta = [])` | `bool` | Save an event with optional metadata |
| `Model::viaEvents()` | `Builder` | Elasticsearch Eloquent builder for the event model |
| `Model::eventSearch(string $event)` | `Collection` | Shorthand for `viaEvents()->where('event', $event)->get()` |

### EventModel (your event model class)

| Method | Returns | Description |
|---|---|---|
| `$event->model()` | `BelongsTo` | Relationship to the base model (eager-loadable) |
| `$event->asModel()` | `?Model` | Direct lookup of the base model |
| `EventModel::saveEvent(Model $model, string $event, array $meta = [])` | `bool` | Create an event record |
| `EventModel::deleteAllEvents(Model $model)` | `void` | Delete all events for a model |
| `EventModel::validateSchema()` | `array` | Create the Elasticsearch index if it doesn't exist |

---

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## Credits

- [David Philip](https://github.com/pdphilip)

## License

The MIT License (MIT). See [License File](LICENSE.md) for details.
