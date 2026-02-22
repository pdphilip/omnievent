<?php

use PDPhilip\OmniEvent\OmniEvent;

it('resolves event model class name from config namespace', function () {
    $model = new class extends \Illuminate\Database\Eloquent\Model {};

    $class = OmniEvent::fetchEventModelClass($model);
    $expected = config('omnievent.namespaces.events').'\\'.class_basename($model).'Event';

    expect($class)->toBe($expected);
});

it('builds request data as array', function () {
    $data = OmniEvent::buildRequest();

    expect($data)->toBeArray();
    // In test context, CfRequest resolves with defaults (IP, user agent parsing)
    // Key structure should include standard fields when available
    if (! empty($data)) {
        $validKeys = ['ip', 'browser', 'device', 'deviceType', 'os', 'country', 'region', 'city', 'postal_code', 'lat', 'lon', 'timezone', 'is_bot', 'geo'];
        foreach (array_keys($data) as $key) {
            expect($validKeys)->toContain($key);
        }
    }
});

it('returns empty array when event models directory does not exist', function () {
    config()->set('omnievent.app_paths.events', 'NonExistent/Path/');

    $models = OmniEvent::allRegisteredEventModels();

    expect($models)->toBeArray()->toBeEmpty();
});
