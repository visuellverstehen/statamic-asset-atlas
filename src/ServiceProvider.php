<?php

namespace VV\AssetAtlas;

use Statamic\Providers\AddonServiceProvider;
use Statamic\Listeners\UpdateAssetReferences as StatamicUpdateAssetReferences;
use VV\AssetAtlas\Commands\Scan;
use VV\AssetAtlas\Subscribers\UpdateAssetReferences;

class ServiceProvider extends AddonServiceProvider
{   
    public function register()
    {
        $this->app->bind(StatamicUpdateAssetReferences::class, UpdateAssetReferences::class);
            
        $this->app->singleton(Atlas::class, function () {
            return new Atlas();
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
                __DIR__ . '/../database/migrations/create_asset_atlas_table.php' => database_path('migrations/' . date('Y_m_d_His') . '_create_asset_atlas_table.php'),
            ], 'asset_atlas_migrations');
        }
    }
}