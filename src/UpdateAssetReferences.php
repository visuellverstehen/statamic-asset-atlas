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
        
        // Get all items using the atlas.
        // Note that we're searching by the assets' NEW path, not
        // the old path. The order of updating references matters!
        return AssetAtlas::findEntries($this->asset->path(), $this->asset->container()?->handle());
    }
    
    /**
     * Handle the asset deleted event.
     * Update the atlas *after* the actual handler is called.
     */
    public function handleDeleted(AssetDeleted $event)
    {   
        parent::handleDeleted($event);
            
        // TODO
    }
    
    /**
     * Handle the asset replaced event.
     */
    public function handleReplaced(AssetReplaced $event)
    {   
        parent::handleReplaced($event);
            
        // TODO
    }
    
    /**
     * Handle the asset saved event.
     * (This includes actions like moving an asset.)
     * Update the atlas *before* the actual handler is called.
     */
    public function handleSaved(AssetSaved $event)
    {
        $asset = $event->asset;
        
        AssetAtlas::update(
            $asset->getOriginal('path'), 
            $asset->path(), 
            $asset->container()->handle()
        );
        
        parent::handleSaved($event);
    }
    
    /**
     * Replace asset references.
     * This function is functionally identical to the parent 
     * class and only stores the asset, so we can work with 
     * it in `getItemsContainingData()` (see above).
     *
     * @param  \Statamic\Assets\Asset  $asset
     * @param  string  $originalPath
     * @param  string  $newPath
     */
    protected function replaceReferences($asset, $originalPath, $newPath)
    {
        $this->asset = $asset;
        
        parent::replaceReferences($asset, $originalPath, $newPath);
    }
}