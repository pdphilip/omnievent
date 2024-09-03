<?php

namespace PDPhilip\OmniEvent;

use PDPhilip\Elasticsearch\Eloquent\Model;

class OmniEvent {

    //----------------------------------------------------------------------
    // Events
    //----------------------------------------------------------------------

    public static function fetchEventModelClass($baseModel): string
    {
        return config('omnievent.namespaces.events').'\\'.class_basename($baseModel).'Event';
    }

    public static function fetchEventModel($baseModel): Model
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


}
