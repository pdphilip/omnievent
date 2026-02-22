<?php

namespace PDPhilip\OmniEvent\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PDPhilip\OmniEvent\Eventable;

class User extends Model
{
    use Eventable;

    protected $connection = 'sqlite';

    protected static $unguarded = true;

    public static function executeSchema(): void
    {
        $schema = Schema::connection('sqlite');
        $schema->dropIfExists('users');
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }
}
