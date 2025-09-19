<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Testing\AddonTestCase;
use VV\AssetAtlas\ServiceProvider;
use Tests\Concerns\UsesTestFixtures;


abstract class TestCase extends AddonTestCase
{
    use RefreshDatabase;
    use UsesTestFixtures;
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('statamic.editions.pro', true);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
