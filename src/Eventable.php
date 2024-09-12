<?php

declare(strict_types=1);

namespace PDPhilip\OmniEvent;

use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PDPhilip\Elasticsearch\Eloquent\Builder;
use PDPhilip\OmniEvent\Exceptions\EventModelException;

trait Eventable
{
    protected Builder $_eventQuery;

    protected static EventModel $_eventModel;

    protected bool $_modelOnly = false;

    public static function bootEventable(): void
    {
        $eventModel = OmniEvent::fetchEventModel((new static));
        $validated = $eventModel::validateSchema();
        static::$_eventModel = $eventModel;
        if ($validated['success']) {

            static::deleted(function ($model) {
                static::$_eventModel::deleteAllEvents($model);
            });
        } else {
            Log::error('Event tracking failed to boot: '.$validated['message'], $validated);
        }

    }

    /**
     * @throws EventModelException
     */
    public function triggerEvent($event, $meta = []): bool
    {
        $valid = self::validateEventModel();
        if ($valid) {
            return self::$_eventModel::saveEvent($this, $event, $meta);
        }

        return false;

    }

    //----------------------------------------------------------------------
    // Entry points for querying events
    //----------------------------------------------------------------------

    /**
     * @throws EventModelException
     */
    public static function viaEvents(): Builder
    {
        self::validateConnection();
        $eventModel = OmniEvent::fetchEventModel((new static));

        return $eventModel->query();
    }

    /**
     * @throws EventModelException
     */
    public static function eventSearch($event): static
    {
        $self = (new static);
        self::validateConnection();
        $query = self::$_eventModel->query();
        $query->where('event', $event);
        $self->_eventQuery = $query;

        return $self;
    }

    //----------------------------------------------------------------------
    // Query methods
    //----------------------------------------------------------------------

    public function eventFrom($date): static
    {
        $this->_eventQuery->where('created_at', '>=', $date);

        return $this;
    }

    public function eventTo($date): static
    {
        $this->_eventQuery->where('created_at', '<=', $date);

        return $this;
    }

    public function modelOnly(): static
    {
        $this->_modelOnly = true;

        return $this;
    }

    public function whereType($type): static
    {
        $this->_eventQuery->where('model_type', $type);

        return $this;
    }

    //----------------------------------------------------------------------
    // Return methods
    //----------------------------------------------------------------------

    public function getEvents(): ?Collection
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

    public function distinctEvents($modelOnly = false): ?Collection
    {
        $results = $this->_eventQuery->distinct(true)->get('model_id')->load('model');
        if ($this->_modelOnly || $modelOnly) {
            $collection = new Collection;
            $results->each(function ($result) use ($collection) {
                $collection->push($result->model);
            });

            return $collection;
        }

        return self::$_eventModel::transformModelRelationship($results);
    }

    public function paginateDistinctEvents($count = 10): LengthAwarePaginator
    {
        $results = $this->_eventQuery->groupBy('model_id')->with('model')->paginate($count)->withQueryString();
        if ($this->_modelOnly) {
            $results = $this->_eventQuery->groupBy('model_id')->with('model')->paginate($count)->withQueryString()->through(function ($result) {
                return $result->model;
            });
        }

        return $results;
    }

    public function countEvents(): int
    {
        return $this->_eventQuery->count();
    }

    public function paginateEvents($count = 10): LengthAwarePaginator
    {
        return $this->_eventQuery->with('model')->paginate($count)->withQueryString();
    }

    /**
     * @throws EventModelException
     */
    private static function validateEventModel(): bool
    {
        $throw = config('omnievent.throw_exceptions', true);
        if ($throw) {
            return self::validateConnection();
        }

        return self::validateAndContinue();
    }

    /**
     * Test connection to the event model
     *
     * @throws EventModelException
     */
    private static function validateConnection(): bool
    {
        try {
            static::$_eventModel::count();
        } catch (Exception $exception) {
            throw new EventModelException('Event model not accessible', $exception);
        }

        return true;
    }

    private static function validateAndContinue(): bool
    {
        try {
            static::validateConnection();
        } catch (EventModelException|Exception $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());

            return false;
        }

        return true;
    }
}
