<?php

namespace VV\AssetAtlas\Fieldtypes;

use Statamic\Fieldtypes\Assets\Assets as BaseFieldtype;
use VV\AssetAtlas\AssetAtlas;

class Assets extends BaseFieldtype
{
    public function process($data)
    {
        $assetData = parent::process($data);
            
        if (! empty($assetData)
            && ($entry = $this->field()?->parent())
            && ($container = $this->container())
        ) {
            if (is_string($assetData)) {
                AssetAtlas::store($assetData, $entry->id(), $container->handle());
            } else {
                $assetData->each(fn ($assetPath) => AssetAtlas::store($assetPath, $entry->id(), $container->handle()));
            }
        }
        
        return $assetData;
    }
}