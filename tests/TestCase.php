<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Testing\AddonTestCase;
use Tests\Concerns\CreatesTestEntries;
use Tests\Concerns\CreatesTestItems;
use Tests\Concerns\UsesTestFixtures;
use VV\AssetAtlas\ServiceProvider;

abstract class TestCase extends AddonTestCase
{
    use CreatesTestEntries;
    use CreatesTestItems;
    use RefreshDatabase;
    use UsesTestFixtures;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Load structural fixtures (collections, blueprints, asset containers)
        $this->loadFixtures();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        if (! $token = $_SERVER['TEST_TOKEN'] ?? null) {
            return;
        }

        $fixtures = __DIR__."/__fixtures__/test_{$token}";

        foreach ($app['config']->get('statamic.stache.stores', []) as $store => $config) {
            if (isset($config['directory'])) {
                $app['config']->set(
                    "statamic.stache.stores.{$store}.directory",
                    str_replace('/tests/__fixtures__/', "/tests/__fixtures__/test_{$token}/", $config['directory']),
                );
            }
        }

        $app['config']->set('statamic.system.blueprints_path', "{$fixtures}/blueprints");
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
