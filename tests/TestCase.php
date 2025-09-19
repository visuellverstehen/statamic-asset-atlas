<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Testing\AddonTestCase;
use Tests\Concerns\CreatesTestEntries;
use Tests\Concerns\UsesTestFixtures;
use VV\AssetAtlas\ServiceProvider;

abstract class TestCase extends AddonTestCase
{
    use CreatesTestEntries;
    use RefreshDatabase;
    use UsesTestFixtures;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Load structural fixtures (collections, blueprints, asset containers)
        $this->loadFixtures();
    }

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
