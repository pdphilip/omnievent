<?php

namespace PDPhilip\OmniEvent\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PDPhilip\OmniEvent\OmniEventServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            OmniEventServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_omnilens_table.php.stub';
        $migration->up();
        */
    }
}
