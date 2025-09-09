<?php

namespace VV\AssetAtlas;

use Illuminate\Support\Arr;
use Statamic\Data\DataReferenceUpdater;
use Statamic\Facades\AssetContainer;
use VV\AssetAtlas\Concerns\GetsItemType;

class AssetScanner extends DataReferenceUpdater
{   
    use GetsItemType;
    
    protected $itemType;
    
    public function scanForReferences()
    {   
        $this->itemType = $this->getItemType($this->item);
        
        $this->recursivelyUpdateFields($this->getTopLevelFields());
    }
    
    protected function recursivelyUpdateFields($fields, $dottedPrefix = null)
    {
        // Using this function to scan all items for asset references
        
        $this
            ->scanAssetsFieldValues($fields, $dottedPrefix)
            ->scanLinkFieldValues($fields, $dottedPrefix)
            ->scanBardFieldValues($fields, $dottedPrefix)
            ->scanMarkdownFieldValues($fields, $dottedPrefix)
            ->updateNestedFieldValues($fields, $dottedPrefix);
    }
    
    protected function getConfiguredAssetsFieldContainer($field)
    {
        if ($container = $field->get('container')) {
            return $container;
        }
    
        $containers = AssetContainer::all();
    
        return $containers->count() === 1
            ? $containers->first()->handle()
            : null;
    }
    
    private function scanAssetsFieldValues($fields, $dottedPrefix)
    {
        $fields
            ->filter(fn ($field) => $field->type() === 'assets')
            ->each(function ($field) use ($dottedPrefix) {
                $data = $this->item->data()->all();
                $dottedKey = $dottedPrefix . $field->handle();
                
                if (
                    ! ($value = Arr::get($data, $dottedKey)) || 
                    ! ($container = $this->getConfiguredAssetsFieldContainer($field))
                ) {
                    return;
                }
                
                if (is_string($value)) {
                    AssetAtlas::store(
                        $value,
                        $container,
                        $this->item->id(),
                        $this->itemType
                    );
                } else {
                    // TODO
                    ray($value)->red();
                }
            });
        
        return $this;
    }
    
    private function scanLinkFieldValues($fields, $dottedPrefix)
    {
        // TODO
        
        return $this;
    }
    
    private function scanBardFieldValues($fields, $dottedPrefix)
    {
        // TODO
        
        return $this;
    }
    
    private function scanMarkdownFieldValues($fields, $dottedPrefix)
    {
        // TODO
        
        return $this;
    }
}