<?php

use PDPhilip\OmniEvent\Tests\Models\Events\UserEvent;
use PDPhilip\OmniEvent\Tests\Models\User;

beforeEach(function () {
    User::executeSchema();

    $schema = \PDPhilip\Elasticsearch\Schema\Schema::connection('elasticsearch');
    $schema->dropIfExists('user_events');

    UserEvent::validateSchema();
});

it('creates an event via triggerEvent', function () {
    $user = User::create(['name' => 'Max', 'email' => 'max@test.com']);

    $result = $user->triggerEvent('login');

    expect($result)->toBeTrue();

    sleep(1);

    $events = UserEvent::where('model_id', $user->id)->get();
    expect($events)->toHaveCount(1);
    expect($events->first()->event)->toBe('login');
    expect((int) $events->first()->model_id)->toBe($user->id);
});

it('stores metadata with event', function () {
    $user = User::create(['name' => 'Suvi', 'email' => 'suvi@test.com']);

    $user->triggerEvent('purchase', ['amount' => 99.95, 'currency' => 'USD']);
    sleep(1);

    $event = UserEvent::where('model_id', $user->id)->first();
    expect($event->meta)->toBeArray();
    expect($event->meta['amount'])->toBe(99.95);
    expect($event->meta['currency'])->toBe('USD');
});

it('stores model_type from modelType method', function () {
    $user = User::create(['name' => 'Tamsin', 'status' => 'premium']);

    $user->triggerEvent('upgrade');
    sleep(1);

    $event = UserEvent::where('model_id', $user->id)->first();
    expect($event->model_type)->toBe('premium');
});

it('sets timestamp on event', function () {
    $user = User::create(['name' => 'Trevor']);

    $before = time();
    $user->triggerEvent('action');
    sleep(1);

    $event = UserEvent::where('model_id', $user->id)->first();
    expect($event->ts)->toBeGreaterThanOrEqual($before);
    expect($event->ts)->toBeLessThanOrEqual(time());
    expect($event->created_at)->not->toBeNull();
});

it('deletes all events when model is deleted', function () {
    $user = User::create(['name' => 'Max']);

    $user->triggerEvent('login');
    $user->triggerEvent('view');
    $user->triggerEvent('logout');
    sleep(1);

    expect(UserEvent::where('model_id', $user->id)->count())->toBe(3);

    $user->delete();
    sleep(1);

    expect(UserEvent::where('model_id', $user->id)->count())->toBe(0);
});

it('resolves base model via relationship', function () {
    $user = User::create(['name' => 'Suvi', 'email' => 'suvi@test.com']);

    $user->triggerEvent('login');
    sleep(1);

    $event = UserEvent::where('model_id', $user->id)->with('model')->first();
    expect($event->model)->toBeInstanceOf(User::class);
    expect($event->model->name)->toBe('Suvi');
});

it('resolves base model via asModel', function () {
    $user = User::create(['name' => 'Tamsin']);

    $user->triggerEvent('signup');
    sleep(1);

    $event = UserEvent::where('model_id', $user->id)->first();
    $resolved = $event->asModel();

    expect($resolved)->toBeInstanceOf(User::class);
    expect($resolved->name)->toBe('Tamsin');
});
