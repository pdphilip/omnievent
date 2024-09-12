<?php

namespace PDPhilip\OmniEvent;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PDPhilip\Elasticsearch\Eloquent\Builder;

trait Eventable
{
    protected Builder $_eventQuery;

    protected static mixed $_eventModel;

    protected bool $_modelOnly = false;

    public static function bootEventable()
    {
        $eventModel = OmniEvent::fetchEventModel((new static));
        $validated = $eventModel::validateSchema();
        if ($validated['success']) {
            self::$_eventModel = OmniEvent::fetchEventModel((new static));

            static::deleted(function ($model) {
                self::$_eventModel::deleteAllEvents($model);
            });
        } else {
            Log::error('Event tracking failed to boot: '.$validated['message']);
        }

    }

    public function triggerEvent($event, $meta = [])
    {
        self::$_eventModel::saveEvent($this, $event, $meta);

    }

    public static function viaEvents()
    {
        $eventModel = OmniEvent::fetchEventModel((new static));

        return $eventModel::query();
    }

    public static function eventSearch($event)
    {
        $self = (new static);
        $query = self::$_eventModel->query();
        $query->where('event', $event);
        $self->_eventQuery = $query;

        return $self;
    }

    public function eventFrom($date)
    {
        $this->_eventQuery->where('created_at', '>=', $date);

        return $this;
    }

    public function eventTo($date)
    {
        $this->_eventQuery->where('created_at', '<=', $date);

        return $this;
    }

    public function modelOnly()
    {
        $this->_modelOnly = true;

        return $this;
    }

    public function whereType($type)
    {
        $this->_eventQuery->where('model_type', $type);

        return $this;
    }

    public function getEvents()
    {
        $results = $this->_eventQuery->get()->load('model');
        if ($this->_modelOnly) {
            $collection = new Collection;
            $results->each(function ($result) use ($collection) {
                $collection->push($result->model);
            });

            return $collection;
        }

        return self::$_eventModel::transformModelRelationship($results);
    }

    public function distinctEvents()
    {
        $results = $this->_eventQuery->distinct(true)->get('model_id')->load('model');
        if ($this->_modelOnly) {
            $collection = new Collection;
            $results->each(function ($result) use ($collection) {
                $collection->push($result->model);
            });

            return $collection;
        }

        return self::$_eventModel::transformModelRelationship($results);
    }

    public function paginateDistinctEvents($count = 10)
    {
        $results = $this->_eventQuery->groupBy('model_id')->with('model')->paginate($count)->withQueryString();
        if ($this->_modelOnly) {
            $results = $this->_eventQuery->groupBy('model_id')->with('model')->paginate($count)->withQueryString()->through(function ($result) {
                return $result->model;
            });
        }

        return $results;
    }

    public function countEvents()
    {
        return $this->_eventQuery->count();
    }

    public function paginateEvents($count = 10)
    {
        return $this->_eventQuery->with('model')->paginate($count)->withQueryString();
    }
}
