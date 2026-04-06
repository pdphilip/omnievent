<?php

use Illuminate\Support\Collection;
use PDPhilip\Elasticsearch\Eloquent\Builder;
use PDPhilip\Elasticsearch\Schema\Schema;
use PDPhilip\OmniEvent\Tests\Models\Events\UserEvent;
use PDPhilip\OmniEvent\Tests\Models\User;

beforeEach(function () {
    User::executeSchema();

    $schema = Schema::connection('elasticsearch');
    $schema->dropIfExists('user_events');

    UserEvent::validateSchema();
});

it('returns ES builder from viaEvents', function () {
    $builder = User::viaEvents();

    expect($builder)->toBeInstanceOf(Builder::class);
});

it('returns collection from eventSearch', function () {
    $user = User::create(['name' => 'Max']);

    $user->triggerEvent('login');
    $user->triggerEvent('logout');
    sleep(1);

    $logins = User::eventSearch('login');
    expect($logins)->toBeInstanceOf(Collection::class);
    expect($logins)->toHaveCount(1);
    expect($logins->first()->event)->toBe('login');
});

it('queries events with viaEvents builder', function () {
    $max = User::create(['name' => 'Max']);
    $suvi = User::create(['name' => 'Suvi']);

    $max->triggerEvent('login');
    $max->triggerEvent('login');
    $suvi->triggerEvent('login');
    sleep(1);

    $maxLogins = User::viaEvents()
        ->where('event', 'login')
        ->where('model_id', (string) $max->id)
        ->get();

    expect($maxLogins)->toHaveCount(2);
});

it('paginates events via builder', function () {
    $user = User::create(['name' => 'Trevor']);

    for ($i = 0; $i < 5; $i++) {
        $user->triggerEvent('action');
    }
    sleep(1);

    $paginated = User::viaEvents()
        ->where('event', 'action')
        ->paginate(2);

    expect($paginated->total())->toBe(5);
    expect($paginated->perPage())->toBe(2);
    expect($paginated->items())->toHaveCount(2);
});

it('counts events via builder', function () {
    $user = User::create(['name' => 'Tamsin']);

    $user->triggerEvent('view');
    $user->triggerEvent('view');
    $user->triggerEvent('click');
    sleep(1);

    $viewCount = User::viaEvents()->where('event', 'view')->count();
    $clickCount = User::viaEvents()->where('event', 'click')->count();

    expect($viewCount)->toBe(2);
    expect($clickCount)->toBe(1);
});

it('orders events by timestamp', function () {
    $user = User::create(['name' => 'Max']);

    $user->triggerEvent('first');
    $user->triggerEvent('second');
    sleep(1);

    $events = User::viaEvents()
        ->where('model_id', (string) $user->id)
        ->orderBy('ts', 'asc')
        ->get();

    expect($events->first()->event)->toBe('first');
    expect($events->last()->event)->toBe('second');
});
