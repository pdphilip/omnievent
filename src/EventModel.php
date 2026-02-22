<?php

// Eleganced at 2026-02-22 19:15

declare(strict_types=1);

namespace PDPhilip\OmniEvent;

use Exception;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDPhilip\Elasticsearch\Eloquent\Builder as EloquentBuilder;
use PDPhilip\Elasticsearch\Eloquent\Model;
use PDPhilip\Elasticsearch\Query\Builder;
use PDPhilip\Elasticsearch\Schema\Blueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

/**
 * @method static EloquentBuilder query()
 *
 * @property string $_id
 * @property string $model_id
 * @property string $model_type
 * @property string $event
 * @property int $ts
 * @property array $meta
 * @property array $request
 * @property Carbon|null $created_at
 * @property-read mixed $model
 *
 * @mixin Builder
 */
abstract class EventModel extends Model
{
    protected $baseModel;

    const UPDATED_AT = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(config('omnievent.database', 'elasticsearch'));
    }

    // ======================================================================
    // Relationships
    // ======================================================================

    public function model(): BelongsTo
    {
        return $this->belongsTo($this->getBaseModel(), 'model_id');
    }

    public function asModel(): ?BaseModel
    {
        if (! $this->model_id) {
            return null;
        }

        $baseModel = $this->getBaseModel();

        return $baseModel::find($this->model_id);
    }

    // ======================================================================
    // Base Model Resolution
    // ======================================================================

    public function getBaseModel(): string
    {
        if ($this->baseModel) {
            return $this->baseModel;
        }

        return $this->guessBaseModelName();
    }

    public function guessBaseModelName(): string
    {
        $baseTable = $this->getTable();

        $prefix = DB::connection('elasticsearch')->getConfig('index_prefix');
        if ($prefix) {
            $baseTable = str_replace($prefix.'_', '', $baseTable);
        }

        $baseTable = str_replace('_events', '', $baseTable);
        $modelName = Str::studly(Str::singular($baseTable));

        return config('omnievent.namespaces.models', 'App\\Models').'\\'.$modelName;
    }

    // ======================================================================
    // Event Operations
    // ======================================================================

    public static function saveEvent(BaseModel $model, string $event, array $meta = []): bool
    {
        // @phpstan-ignore-next-line
        $eventRecord = new static;

        $eventRecord->model_id = $model->{$model->getKeyName()};
        $eventRecord->event = $event;
        $eventRecord->ts = time();

        if (method_exists($eventRecord, 'modelType')) {
            $modelType = $eventRecord->modelType($model);
            if ($modelType) {
                $eventRecord->model_type = $modelType;
            }
        }

        if ($meta) {
            $eventRecord->meta = $meta;
        }

        if (config('omnievent.save_request')) {
            $requestData = OmniEvent::buildRequest();
            if ($requestData) {
                $eventRecord->request = $requestData;
            }
        }

        $eventRecord->withoutRefresh()->save();

        return true;
    }

    public static function deleteAllEvents(BaseModel $model): void
    {
        static::where('model_id', $model->{$model->getKeyName()})->delete();
    }

    // ======================================================================
    // Schema
    // ======================================================================

    public static function validateSchema(): array
    {
        try {
            // @phpstan-ignore-next-line
            $tableName = (new static)->getTable();
            $index = Schema::getIndex($tableName);

            if ($index) {
                return ['success' => true, 'message' => 'Index exists'];
            }

            Schema::create($tableName, function (Blueprint $index) {
                self::schemaDefinition($index);
            });

            return ['success' => true, 'message' => 'Index created'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected static function schemaDefinition(Blueprint $index): void
    {
        $index->keyword('model_id');
        $index->keyword('model_type');
        $index->keyword('event');
        $index->integer('ts');
        $index->flattened('meta');
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
        $index->geoPoint('request.geo');
    }
}
