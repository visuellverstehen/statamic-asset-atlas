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
        $itemType = $this->getItemType($this->item);
        $itemId = $this->item->id();
        
        $this->atlasItems = collect([]);
        $this->dataToScan = $this->item->data()->all();
        $this->recursivelyUpdateFields($fields);
        
        // Add all collected items to atlas
        $this->atlasItems->unique()->each(function ($item) use ($itemType, $itemId) {
            [$container, $path] = explode('::', $item);
            
            AssetAtlas::store($path, $container, $itemId, $itemType);
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
                ->each(function ($item) use ($itemId) {
                    [$container, $path] = explode('::', $item);
                    
                    AssetAtlas::remove($path, $container, $itemId);
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
    
    protected function findAssetsInImageNodes($bardValue)
    {
        collect(Arr::dot($bardValue))
            ->filter(function ($value, $key) {
                // Only keep image nodes and get their dotted path within the node
                return preg_match('/(.*)\.(type)/', $key) && $value === 'image';
            })
            ->each(function ($value, $key) use ($bardValue) {
                // Modify the dotted path to fetch the asset src from the node
                $key = str_replace('.type', '.attrs.src', $key);
                $asset = Arr::get($bardValue, $key);
                
                // Extract data (asset is stored as `asset::{container}::{path})
                $assetData = explode('::', $asset);
                $this->push($assetData[1], $assetData[2]);
            });
    }
    
    protected function findAssetsInLinkNodes($bardValue)
    {
        collect(Arr::dot($bardValue))
            ->filter(function ($value, $key) {
                // Only keep link nodes and get their dotted path within the node
                return preg_match('/(.*)\.(type)/', $key) && $value === 'link';
            })
            ->each(function ($value, $key) use ($bardValue) {
                // Modify the dotted path to fetch the asset src from the node
                $key = str_replace('.type', '.attrs.href', $key);
                $asset = Arr::get($bardValue, $key);
                
                // Extract data (asset is stored as `statamic://asset::{container}::{path})
                $assetData = str_replace('statamic://asset::', '', $asset);
                $assetData = explode('::', $assetData);
                $this->push($assetData[0], $assetData[1]);
            });
    }
    
    protected function findAssetsInStringValue(string $value)
    {
        if (! preg_match_all('/[("]statamic:\/\/asset::([^()"]*)::([^)"]*)[)"]/im', $value, $matches)) {
            return;
        }
        
        foreach ($matches[1] as $index => $container) {
            $this->push($container, $matches[2][$index]);
        }
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
                    $this->findAssetsInStringValue($value);
                } else {
                    $this->findAssetsInImageNodes($value);
                    $this->findAssetsInLinkNodes($value);
                }
            });
        
        return $this;
    }
    
    private function scanMarkdownFieldValues($fields, $dottedPrefix)
    {
        $fields
            ->filter(fn ($field) => $field->type() === 'markdown')
            ->each(function ($field) use ($dottedPrefix) {
                $dottedKey = $dottedPrefix . $field->handle();
                
                if ($value = Arr::get($this->dataToScan, $dottedKey)) {
                    $this->findAssetsInStringValue($value);
                }
            });
        
        return $this;
    }
}