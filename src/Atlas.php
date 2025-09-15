<?php

namespace VV\AssetAtlas;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalVariables;
use Statamic\Facades\Term;
use Statamic\Facades\User;

class Atlas
{
    protected $lazyThreshold = 500;
    protected $itemTypes = ['entry', 'term', 'global_var', 'user'];
    
    public function find(string $assetPath, string $containerHandle, ?string $itemType = null): Collection|LazyCollection
    {   
        $query = AssetReference::query()
            ->where('asset_path', $assetPath)
            ->where('asset_container', $containerHandle);
        
        if ($itemType && in_array($itemType, $this->itemTypes)) {
            $query->where('item_type', $itemType);
        }
        
        return ($query->count() > $this->lazyThreshold)
            ? $query->lazy()
            : $query->get();
    }
    
    public function findAll(string $assetPath, string $containerHandle): Collection|LazyCollection
    {   
        return $this->find($assetPath, $containerHandle)
            ->map(function ($ref)  {
                return match ($ref->item_type) {
                    'entry'         => Entry::find($ref->item_id),
                    'global_var'    => GlobalVariables::find($ref->item_id),
                    'term'          => Term::find($ref->item_id),
                    'user'          => User::find($ref->item_id),
                };
            });
    }
    
    public function findEntries(string $assetPath, string $containerHandle): Collection|LazyCollection
    {
        return $this->find($assetPath, $containerHandle, 'entry')
            ->map(fn ($ref) => Entry::find($ref->item_id));
    }
    
    public function findGlobalVar(string $assetPath, string $containerHandle): Collection|LazyCollection
    {
        return $this
            ->find($assetPath, $containerHandle, 'global_var')
            ->map(fn ($ref) => GlobalVariables::find($ref->item_id));
    }
    
    public function findTerms(string $assetPath, string $containerHandle): Collection|LazyCollection
    {
        return $this
            ->find($assetPath, $containerHandle, 'term')
            ->map(fn ($ref) => Term::find($ref->item_id));
    }
    
    public function findUsers(string $assetPath, string $containerHandle): Collection|LazyCollection
    {
        return $this
            ->find($assetPath, $containerHandle, 'user')
            ->map(fn ($ref) => User::find($ref->item_id));
    }
    
    public function remove(string $assetPath, string $containerHandle, string $itemId)
    {
        AssetReference::query()
            ->where('asset_path', $assetPath)
            ->where('asset_container', $containerHandle)
            ->where('item_id', $itemId)
            ->delete();
    }
    
    public function removeAllByAsset(string $assetPath, string $containerHandle): void
    {
        AssetReference::query()
            ->where('asset_path', $assetPath)
            ->where('asset_container', $containerHandle)
            ->delete();
    }
    
    public function removeAllByItem(string $itemId): void
    {
        AssetReference::query()
            ->where('item_id', $itemId)
            ->delete();
    }
    
    public function store(string $assetPath, string $containerHandle, string $itemId, string $itemType): void
    {
        if (! in_array($itemType, $this->itemTypes)) {
            throw new AssetAtlasException("Invalid item type: " . $itemType);
        }
        
        AssetReference::firstOrCreate([
            'asset_path' => $assetPath,
            'asset_container' => $containerHandle,
            'item_id' => $itemId,
            'item_type' => $itemType,
        ])
        ->save();
    }
    
    public function storeEntry(string $assetPath, string $containerHandle, string $entryId): void
    {
        $this->store($assetPath, $containerHandle, $entryId, 'entry');
    }
    
    public function update(string $oldPath, string $newPath, string $container): void
    {
        if ($oldPath === $newPath) {
            return;
        }
        
        $refs = $this->find($oldPath, $container);
        
        $refs->each(function ($ref) use ($newPath) {
            $ref->asset_path = $newPath;
            $ref->save();
        });
    }
}