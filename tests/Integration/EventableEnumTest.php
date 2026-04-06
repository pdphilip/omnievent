<?php

use PDPhilip\Elasticsearch\Schema\Schema;
use PDPhilip\OmniEvent\Tests\Models\Events\UserEvent;
use PDPhilip\OmniEvent\Tests\Models\User;

beforeEach(function () {
    User::executeSchema();

    $schema = Schema::connection('elasticsearch');
    $schema->dropIfExists('user_events');

    UserEvent::validateSchema();
});

enum TestEventType: string
{
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case PURCHASE = 'purchase';
}

enum TestEventInt: int
{
    case LOGIN = 1;
    case LOGOUT = 2;
}

it('accepts a backed string enum in triggerEvent', function () {
    $user = User::create(['name' => 'Max', 'email' => 'max@test.com']);

    $result = $user->triggerEvent(TestEventType::LOGIN);

    expect($result)->toBeTrue();

    sleep(1);

    $event = UserEvent::where('model_id', $user->id)->first();
    expect($event->event)->toBe('login');
});

it('accepts a backed int enum in triggerEvent', function () {
    $user = User::create(['name' => 'Suvi', 'email' => 'suvi@test.com']);

    $result = $user->triggerEvent(TestEventInt::LOGIN);

    expect($result)->toBeTrue();

    sleep(1);

    $event = UserEvent::where('model_id', $user->id)->first();
    expect($event->event)->toBe('1');
});

it('still accepts a plain string in triggerEvent', function () {
    $user = User::create(['name' => 'Tamsin']);

    $result = $user->triggerEvent('signup');

    expect($result)->toBeTrue();

    sleep(1);

    $event = UserEvent::where('model_id', $user->id)->first();
    expect($event->event)->toBe('signup');
});

it('accepts a backed enum in eventSearch', function () {
    $user = User::create(['name' => 'Trevor']);

    $user->triggerEvent(TestEventType::PURCHASE, ['amount' => 50]);
    sleep(1);

    $results = User::eventSearch(TestEventType::PURCHASE);

    expect($results)->toHaveCount(1);
    expect($results->first()->event)->toBe('purchase');
});

it('accepts a plain string in eventSearch', function () {
    $user = User::create(['name' => 'Ayla']);

    $user->triggerEvent('logout');
    sleep(1);

    $results = User::eventSearch('logout');

    expect($results)->toHaveCount(1);
    expect($results->first()->event)->toBe('logout');
});

it('stores metadata when using enum event', function () {
    $user = User::create(['name' => 'Max']);

    $user->triggerEvent(TestEventType::PURCHASE, ['amount' => 99.95, 'currency' => 'USD']);
    sleep(1);

    $event = UserEvent::where('model_id', $user->id)->first();
    expect($event->event)->toBe('purchase');
    expect($event->meta['amount'])->toBe(99.95);
    expect($event->meta['currency'])->toBe('USD');
});
