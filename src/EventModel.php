<?php

declare(strict_types=1);

namespace PDPhilip\OmniEvent;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDPhilip\Elasticsearch\Eloquent\Builder as EloquentBuilder;
use PDPhilip\Elasticsearch\Eloquent\Model;
use PDPhilip\Elasticsearch\Query\Builder;
use PDPhilip\Elasticsearch\Relations\BelongsTo;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use PDPhilip\OmniEvent\Traits\Timer;

/**
 * @method static EloquentBuilder query()
 *
 * *****Fields*******
 *
 * @property string $_id
 * @property string $model_id
 * @property string $model_type
 * @property string $event
 * @property int $ts
 * @property array $meta
 * @property array $request
 * @property Carbon|null $created_at
 * @property-read mixed $hits
 * @property-read mixed $model
 *
 * @mixin Builder
 */
abstract class EventModel extends Model
{
    use Timer;

    public $connection = 'elasticsearch';

    protected $baseModel;

    const UPDATED_AT = null;

    public function model(): BelongsTo
    {
        return $this->belongsTo($this->getBaseModel(), 'model_id');
    }

    public function guessBaseModelName(): string
    {
        $baseTable = $this->getTable();
        $prefix = DB::connection('elasticsearch')->getConfig('index_prefix');
        if ($prefix) {
            $baseTable = str_replace($prefix.'_', '', $baseTable);
        }

        $baseTable = str_replace('_events', '', $baseTable);
        $baseModel = Str::singular($baseTable);

        $baseModel = Str::studly($baseModel);

        return 'App\Models\\'.$baseModel;
    }

    public function getBaseModel(): string
    {
        if (! $this->baseModel) {
            return $this->guessBaseModelName();
        }

        return $this->baseModel;
    }

    public function asModel()
    {
        if ($this->model_id) {
            $baseModel = $this->getBaseModel();

            return $baseModel::find($this->model_id);
        }

        return null;

    }

    public static function saveEvent($model, $event, $meta = []): bool
    {
        try {
            $model_id = $model->{$model->getKeyName()};

            // @phpstan-ignore-next-line
            $eventModel = new static;
            $modelType = null;
            if (method_exists($eventModel, 'modelType')) {
                $modelType = $eventModel->modelType($model);
            }
            $eventModel->model_id = $model_id;
            if ($modelType) {
                $eventModel->model_type = $modelType;
            }
            $eventModel->event = $event;
            if ($meta) {
                if (! is_array($meta)) {
                    $meta = [
                        'key' => $meta,
                    ];
                }
                $eventModel->meta = $meta;
            }
            if (config('omnievent.save_request')) {
                $eventModel->request = OmniEvent::buildRequest();
            }
            $eventModel->ts = time();
            $eventModel->saveWithoutRefresh();

        } catch (Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());

            return false;
        }

        return true;
    }

    public static function validateSchema(): array
    {
        $validated['success'] = false;
        $validated['data'] = [];
        $validated['message'] = '';
        try {
            // @phpstan-ignore-next-line
            $eventModel = new static;
            $eventModel->startTimer();
            $tableName = $eventModel->getTable();
            $index = Schema::getIndex($tableName);
            $validated['message'] = 'Index Exists';
            if (! $index) {
                Schema::create($tableName, function (IndexBlueprint $index) {
                    $index->keyword('model_id');
                    $index->keyword('model_type');
                    $index->keyword('event');
                    $index->integer('ts');
                    $index->mapProperty('meta', 'flattened');
                    $index->keyword('request.ip');
                    $index->keyword('request.browser');
                    $index->keyword('request.device');
                    $index->keyword('request.deviceType');
                    $index->keyword('request.os');
                    $index->keyword('request.country');
                    $index->keyword('request.region');
                    $index->keyword('request.city');
                    $index->keyword('request.postal_code');
                    $index->float('request.lat');
                    $index->float('request.lon');
                    $index->keyword('request.timezone');
                    $index->boolean('request.is_bot');
                    $index->integer('request.threat_score');
                    $index->geo('request.geo');

                });
                $validated['message'] = 'Index Created';
            }
            $validated['success'] = true;
            $validated['data'] = $eventModel->getTime();

            return $validated;
        } catch (\Exception $e) {
            $validated['message'] = $e->getMessage();
        }

        return $validated;
    }

    public static function transformModelRelationship($collection)
    {
        // @phpstan-ignore-next-line
        $baseModel = (new static)->getBaseModel();
        $modelName = Str::lcfirst(class_basename($baseModel));

        return $collection->transform(function ($item) use ($modelName) {

            $item->{$modelName.'_id'} = $item->model_id;
            if (isset($item->model_id_count)) {
                // @phpstan-ignore-next-line
                $item->hits = $item->model_id_count;
                unset($item->model_id_count);
            }
            if (isset($item->model)) {
                $item->{$modelName} = $item->model;
                unset($item->model);
            }

            return $item;
        });
    }

    public static function deleteAllEvents($model): void
    {
        $model_id = $model->{$model->getKeyName()};

        $events = static::where('model_id', $model_id)->get();
        $events->each(function ($event) {
            $event->delete();
        });
    }
}
