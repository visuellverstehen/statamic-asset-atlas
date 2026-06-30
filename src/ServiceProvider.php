<?php

namespace VV\AssetAtlas;

use Statamic\Listeners\UpdateAssetReferences as StatamicUpdateAssetReferences;
use Statamic\Providers\AddonServiceProvider;
use VV\AssetAtlas\Commands\Scan;
use VV\AssetAtlas\Overrides\UpdateAssetReferences;

class ServiceProvider extends AddonServiceProvider
{
    public function register()
    {
        // Replace Statamic's UpdateAssetReferences listener with our atlas-backed
        // subclass. Kept out of src/Subscribers and src/Listeners (both auto-discovered
        // by the addon) so it's registered once here, not a second time by discovery.
        $this->app->bind(StatamicUpdateAssetReferences::class, UpdateAssetReferences::class);

        $this->app->singleton(Atlas::class, function () {
            return new Atlas;
        });
    }

    public function boot()
    {
        parent::boot();

        $this->commands([
            Scan::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_asset_atlas_table.php' => database_path('migrations/'.date('Y_m_d_His').'_create_asset_atlas_table.php'),
            ], 'asset_atlas_migrations');
        }
    }
}
