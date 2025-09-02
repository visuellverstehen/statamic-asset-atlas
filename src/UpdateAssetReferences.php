<?php

namespace VV\AssetAtlas;

use Statamic\Events\AssetDeleted;
use Statamic\Events\AssetReplaced;
use Statamic\Events\AssetSaved;
use Statamic\Listeners\UpdateAssetReferences as BaseListener;

class UpdateAssetReferences extends BaseListener
{
    protected $asset;
    
    /**
     * Get all items that contain an asset.
     * Try to use AssetAtlas, if required data is available
     * or pass on to original function, fetching everything.
     *
     * @return \Illuminate\Support\Collection|\Illuminate\Support\LazyCollection
     */
    public function getItemsContainingData()
    {       
        // If we don't have a stored asset, fallback to original function.
        if (! $this->asset) {
            return parent::getItemsContainingData();
        }
        
        // TODO: get other stuff apart from entries
        
        // Get all items using the atlas, based on the 
        // OLD path, so *before* updating the atlas.
        return AssetAtlas::findEntries($this->asset->getOriginal('path'), $this->asset->container()?->handle());
    }
    
    /**
     * Handle the asset deleted event.
     */
    public function handleDeleted(AssetDeleted $event)
    {   
        $this->asset = $event->asset;
        
        parent::handleDeleted($event);
        
        AssetAtlas::remove(
            $this->asset->getOriginal('path'),
            $this->asset->container()->handle()
        );
    }
    
    /**
     * Handle the asset replaced event.
     */
    public function handleReplaced(AssetReplaced $event)
    {   
        $this->asset = $event->originalAsset;
        
        parent::handleReplaced($event);
            
        AssetAtlas::update(
            $event->originalAsset->path(),
            $event->newAsset->path(),
            $event->originalAsset->container()->handle()
        );
    }
    
    /**
     * Handle the asset saved event.
     * (This includes actions like moving an asset.)
     */
    public function handleSaved(AssetSaved $event)
    {
        $this->asset = $event->asset;
        
        parent::handleSaved($event);
        
        AssetAtlas::update(
            $this->asset->getOriginal('path'), 
            $this->asset->path(), 
            $this->asset->container()->handle()
        );
    }
}