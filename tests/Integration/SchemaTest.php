<?php

use PDPhilip\Elasticsearch\Schema\Schema;
use PDPhilip\OmniEvent\Tests\Models\Events\UserEvent;

beforeEach(function () {
    Schema::connection('elasticsearch')->dropIfExists('user_events');
});

it('creates index on validateSchema', function () {
    $result = UserEvent::validateSchema();

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Index created');
    expect(Schema::connection('elasticsearch')->hasTable('user_events'))->toBeTrue();
});

it('reports existing index on second call', function () {
    UserEvent::validateSchema();

    $result = UserEvent::validateSchema();

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Index exists');
});
