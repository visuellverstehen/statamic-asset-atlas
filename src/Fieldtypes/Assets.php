<?php

namespace VV\AssetAtlas\Fieldtypes;

use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Globals\GlobalSet;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fieldtypes\Assets\Assets as BaseFieldtype;
use VV\AssetAtlas\AssetAtlas;

class Assets extends BaseFieldtype
{
    public function process($data)
    {
        $assetData = parent::process($data);
            
        if (! empty($assetData)
            && ($parent = $this->field()?->parent())
            && ($container = $this->container())
            && ($type = $this->getParentType($parent))
        ) { 
            if (is_string($assetData)) {
                AssetAtlas::store($assetData, $container->handle(), $parent->id(), $type);
            } else {
                $assetData->each(fn ($assetPath) => AssetAtlas::store($assetPath, $container->handle(), $parent->id(), $type));
            }
        }
        
        return $assetData;
    }
    
    protected function getParentType($parent): ?string
    {
        switch (true) {
            case $parent instanceof Entry: return 'entry';
            case $parent instanceof Term: return 'term';
            case $parent instanceof GlobalSet: return 'global_set';
            case $parent instanceof User: return 'user';
        }
    }
}