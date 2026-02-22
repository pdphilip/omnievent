<?php

use PDPhilip\OmniEvent\Tests\Models\Events\UserEvent;
use PDPhilip\OmniEvent\Tests\Models\User;

it('resolves base model from property', function () {
    $eventModel = new UserEvent;
    expect($eventModel->getBaseModel())->toBe(User::class);
});

it('uses config database connection', function () {
    $eventModel = new UserEvent;
    expect($eventModel->getConnectionName())->toBe('elasticsearch');
});

it('has no UPDATED_AT column', function () {
    expect(UserEvent::UPDATED_AT)->toBeNull();
});

it('defines belongsTo relationship to base model', function () {
    $eventModel = new UserEvent;
    $relation = $eventModel->model();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($relation->getForeignKeyName())->toBe('model_id');
});

it('returns null from asModel when no model_id', function () {
    $eventModel = new UserEvent;
    expect($eventModel->asModel())->toBeNull();
});
