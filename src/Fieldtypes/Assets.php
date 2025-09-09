<?php

namespace VV\AssetAtlas\Fieldtypes;

use Statamic\Fieldtypes\Assets\Assets as BaseFieldtype;
use VV\AssetAtlas\AssetAtlas;
use VV\AssetAtlas\Concerns\GetsItemType;

class Assets extends BaseFieldtype
{
    use GetsItemType;
    
    public function process($data)
    {
        $assetData = parent::process($data);
            
        if (! empty($assetData)
            && ($parent = $this->field()?->parent())
            && ($container = $this->container())
            && ($type = $this->getItemType($parent))
        ) { 
            if (is_string($assetData)) {
                AssetAtlas::store($assetData, $container->handle(), $parent->id(), $type);
            } else {
                $assetData->each(fn ($assetPath) => AssetAtlas::store($assetPath, $container->handle(), $parent->id(), $type));
            }
        }
        
        return $assetData;
    }
}