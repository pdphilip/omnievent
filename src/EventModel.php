<?php

namespace PDPhilip\OmniEvent;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDPhilip\Elasticsearch\Eloquent\Model;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use PDPhilip\OmniEvent\Traits\Timer;

/**
 * *****Fields*******
 *
 * @property string $_id
 * @property string $model_id
 * @property string $model_type
 * @property string $event
 * @property int $ts
 * @property array $meta
 * @property Carbon|null $created_at
 * @property-read mixed $hits
 * @property-read mixed $model
 */
abstract class EventModel extends Model
{
    use Timer;

    public $connection = 'elasticsearch';

    protected $baseModel;

    const UPDATED_AT = null;

    public function model()
    {
        return $this->belongsTo($this->getBaseModel(), 'model_id');
    }

    public function guessBaseModelName()
    {
        $baseTable = $this->getTable();
        $prefix = DB::connection('elasticsearch')->getConfig('index_prefix');
        if ($prefix) {
            $baseTable = str_replace($prefix.'_', '', $baseTable);
        }

        $baseTable = str_replace('_events', '', $baseTable);
        $baseModel = Str::singular($baseTable);

        $baseModel = Str::studly($baseModel);
        $baseModel = 'App\Models\\'.$baseModel;

        return $baseModel;
    }

    public function getBaseModel()
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

    public static function saveEvent($model, $event, $meta = [])
    {
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
        $eventModel->ts = time();
        $eventModel->saveWithoutRefresh();

        return $eventModel;
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

    public static function deleteAllEvents($model)
    {
        $model_id = $model->{$model->getKeyName()};

        $events = static::where('model_id', $model_id)->get();
        $events->each(function ($event) {
            $event->delete();
        });
    }
}
