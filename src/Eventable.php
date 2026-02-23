<?php

// Eleganced at 2026-02-22 19:15

declare(strict_types=1);

namespace PDPhilip\OmniEvent;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PDPhilip\Elasticsearch\Eloquent\Builder;

trait Eventable
{
    protected static EventModel $eventModel;

    public static function bootEventable(): void
    {
        $eventModel = OmniEvent::fetchEventModel(new static);
        $validated = $eventModel::validateSchema();

        static::$eventModel = $eventModel;

        if ($validated['success']) {
            static::deleted(fn ($model) => static::$eventModel::deleteAllEvents($model));
        } else {
            Log::error('OmniEvent: Failed to boot - '.$validated['message']);
        }
    }

    public function triggerEvent(string|\BackedEnum $event, array $meta = []): bool
    {
        try {
            if ($event instanceof \BackedEnum) {
                $event = (string) $event->value;
            }

            return static::$eventModel::saveEvent($this, $event, $meta);
        } catch (Exception $e) {
            if (config('omnievent.throw_exceptions', true)) {
                throw $e;
            }

            Log::error('OmniEvent: '.$e->getMessage());

            return false;
        }
    }

    public static function viaEvents(): Builder
    {
        return static::$eventModel->query();
    }

    public static function eventSearch(string|\BackedEnum $event): Collection
    {
        if ($event instanceof \BackedEnum) {
            $event = (string) $event->value;
        }

        return static::viaEvents()->where('event', $event)->get();
    }
}
