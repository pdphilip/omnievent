<?php

namespace PDPhilip\OmniEvent\Tests\Models\Events;

use PDPhilip\OmniEvent\EventModel;
use PDPhilip\OmniEvent\Tests\Models\User;

class UserEvent extends EventModel
{
    protected $baseModel = User::class;

    public function modelType(User $model): ?string
    {
        return $model->status;
    }
}
