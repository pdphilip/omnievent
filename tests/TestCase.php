<?php

namespace PDPhilip\OmniEvent\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PDPhilip\Elasticsearch\ElasticServiceProvider;
use PDPhilip\OmniEvent\OmniEventServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ElasticServiceProvider::class,
            OmniEventServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('database.connections.elasticsearch', [
            'driver' => 'elasticsearch',
            'auth_type' => 'http',
            'hosts' => ['http://localhost:9200'],
            'options' => ['logging' => true],
        ]);

        $app['config']->set('omnievent.database', 'elasticsearch');
        $app['config']->set('omnievent.throw_exceptions', true);
        $app['config']->set('omnievent.save_request', false);
        $app['config']->set('omnievent.namespaces', [
            'models' => 'PDPhilip\\OmniEvent\\Tests\\Models',
            'events' => 'PDPhilip\\OmniEvent\\Tests\\Models\\Events',
        ]);
    }
}
