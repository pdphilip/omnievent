<?php

declare(strict_types=1);

namespace PDPhilip\OmniEvent;

use PDPhilip\CfRequest\CfRequest;
use PDPhilip\Elasticsearch\Eloquent\Model;

class OmniEvent
{
    //----------------------------------------------------------------------
    // Events
    //----------------------------------------------------------------------

    public static function fetchEventModelClass($baseModel): string
    {
        return config('omnievent.namespaces.events').'\\'.class_basename($baseModel).'Event';
    }

    public static function fetchEventModel($baseModel): EventModel
    {
        $eventModel = self::fetchEventModelClass($baseModel);

        return new $eventModel;

    }

    public static function getEventModel($eventModel): Model
    {
        $eventModel = config('omnievent.namespaces.events').'\\'.$eventModel;

        return new $eventModel;
    }

    public static function returnAllRegisteredEventModels()
    {
        $eventModels = [];

        foreach (glob(app_path(config('omnievent.app_paths.events').'*.php')) as $file) {
            $eventModel = (config('omnievent.namespaces.events').'\\'.basename($file, '.php'));
            $eventModels[] = (new $eventModel)::class;
        }

        return $eventModels;
    }

    public static function buildRequest()
    {
        $request = new CfRequest;
        $device = $request->deviceBrand();
        $model = $request->deviceModel();
        if ($device !== $model) {
            $device = $device.' '.$model;
        }
        $requestData = [
            'ip' => $request->ip(),
            'browser' => $request->browser(),
            'device' => $device,
            'deviceType' => $request->deviceType(),
            'os' => $request->os(),
        ];

        $cf['country'] = $request->country();
        $cf['region'] = $request->region();
        $cf['city'] = $request->city();
        $cf['postal_code'] = $request->postalCode();
        $cf['lat'] = $request->lat();
        $cf['lon'] = $request->lon();
        $cf['timezone'] = $request->timezone();
        $cf['is_bot'] = $request->isBot();
        $cf['threat_score'] = $request->threatScore();
        $cf['geo'] = null;
        if ($cf['lat'] && $cf['lon']) {
            $cf['geo'] = [
                'type' => 'Point',
                'coordinates' => [
                    (float) $cf['lon'],
                    (float) $cf['lat'],
                ],
            ];
        }

        foreach ($cf as $key => $value) {
            if ($value) {
                $requestData[$key] = $value;
            }
        }

        return $requestData;
    }
}
