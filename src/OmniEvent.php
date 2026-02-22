<?php

// Eleganced at 2026-02-22 19:15

declare(strict_types=1);

namespace PDPhilip\OmniEvent;

use Exception;
use PDPhilip\CfRequest\CfRequest;

class OmniEvent
{
    public static function fetchEventModelClass(object $baseModel): string
    {
        return config('omnievent.namespaces.events').'\\'.class_basename($baseModel).'Event';
    }

    public static function fetchEventModel(object $baseModel): EventModel
    {
        $class = self::fetchEventModelClass($baseModel);

        return new $class;
    }

    public static function resolveEventModel(string $eventModelName): EventModel
    {
        $class = config('omnievent.namespaces.events').'\\'.$eventModelName;

        return new $class;
    }

    public static function allRegisteredEventModels(): array
    {
        $path = app_path(config('omnievent.app_paths.events'));

        if (! is_dir($path)) {
            return [];
        }

        $files = glob($path.'*.php');

        if (! $files) {
            return [];
        }

        $namespace = config('omnievent.namespaces.events');

        return array_map(
            fn (string $file) => $namespace.'\\'.basename($file, '.php'),
            $files
        );
    }

    // ======================================================================
    // Request Capture
    // ======================================================================

    public static function buildRequest(): array
    {
        try {
            $request = app(CfRequest::class);
        } catch (Exception) {
            return [];
        }

        $device = $request->deviceBrand();
        $model = $request->deviceModel();

        $data = [
            'ip' => $request->ip(),
            'browser' => $request->browser(),
            'device' => $device !== $model ? $device.' '.$model : $device,
            'deviceType' => $request->deviceType(),
            'os' => $request->os(),
            'country' => $request->country(),
            'region' => $request->region(),
            'city' => $request->city(),
            'postal_code' => $request->postalCode(),
            'lat' => $request->lat(),
            'lon' => $request->lon(),
            'timezone' => $request->timezone(),
            'is_bot' => $request->isBot(),
        ];

        if ($data['lat'] && $data['lon']) {
            $data['geo'] = [
                'type' => 'Point',
                'coordinates' => [(float) $data['lon'], (float) $data['lat']],
            ];
        }

        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }
}
