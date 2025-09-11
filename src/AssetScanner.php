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
    protected $checkOriginal = false;
    
    private $dataToScan;
    private $atlasItems;
    
    public function scanForReferences()
    {   
        $fields = $this->getTopLevelFields();
        $this->itemType = $this->getItemType($this->item);
        
        $this->atlasItems = collect([]);
        $this->dataToScan = $this->item->data()->all();
        $this->recursivelyUpdateFields($fields);
        
        // Add all collected items to atlas
        $this->atlasItems->unique()->each(function ($item) {
            [$container, $path] = explode('::', $item);
            
            AssetAtlas::store(
                $path,
                $container,
                $this->item->id(),
                $this->itemType
            );
        });
        
        // If activated, check the items original data
        // to ensure to remove unused references.
        if ($this->checkOriginal) {
            $fromData = $this->atlasItems;
            
            $this->atlasItems = collect([]);
            $this->dataToScan = $this->item->getOriginal();
            $this->recursivelyUpdateFields($fields);
            
            $this->atlasItems
                ->unique()
                ->diffKeys($fromData)
                ->each(function ($item) {
                    [$container, $path] = explode('::', $item);
                    
                    AssetAtlas::remove($path, $container);
                });
        }
    }
    
    public function checkOriginal($check = true)
    {
        $this->checkOriginal = $check;
        
        return $this;
    }
    
    protected function push(string $container, string $path)
    {
        $this->atlasItems->push($container . '::' . $path);
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
    
    protected function getStatamicUrlFromStringValue(string $value)
    {
        
    }
    
    private function scanAssetsFieldValues($fields, $dottedPrefix)
    {   
        $fields
            ->filter(fn ($field) => $field->type() === 'assets')
            ->each(function ($field) use ($dottedPrefix) {
                $dottedKey = $dottedPrefix . $field->handle();
                
                if (
                    ! ($path = Arr::get($this->dataToScan, $dottedKey)) || 
                    ! ($container = $this->getConfiguredAssetsFieldContainer($field))
                ) {
                    return;
                }
                
                if (is_string($path)) {
                    $this->push($container, $path);
                } else if (is_array($path)) {
                    collect($path)
                    ->each(fn ($p) => $this->push($container, $p));
                }
            });
        
        return $this;
    }
    
    private function scanLinkFieldValues($fields, $dottedPrefix)
    {   
        $fields
            ->filter(fn ($field) => $field->type() === 'link')
            ->each(function ($field) use ($dottedPrefix) {
                $dottedKey = $dottedPrefix . $field->handle();
                
                $value = Arr::get($this->dataToScan, $dottedKey);
                    
                if (! is_string($value) || ! str_contains($value, "asset::")) {
                    return;
                }
                
                $assetData = explode('::', $value);
                $this->push($assetData[1], $assetData[2]);
            });
        
        return $this;
    }
    
    private function scanBardFieldValues($fields, $dottedPrefix)
    {   
        $fields
            ->filter(fn ($field) => $field->type() === 'bard')
            ->each(function ($field) use ($dottedPrefix) {
                $dottedKey = $dottedPrefix . $field->handle();
                
                if (! $value = Arr::get($this->dataToScan, $dottedKey)) {
                    return;
                }
                
                if (is_string($value)) {
                    // TODO
                } else {
                    // TODO
                }
            });
        
        return $this;
    }
    
    private function scanMarkdownFieldValues($fields, $dottedPrefix)
    {
        // TODO
        
        return $this;
    }
}