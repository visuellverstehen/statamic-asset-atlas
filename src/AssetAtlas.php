<?php

namespace VV\AssetAtlas;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Statamic\Facades\Entry;

class AssetAtlas
{
    public static function find(string $assetPath, string $containerHandle, bool $lazy = false): Collection|LazyCollection
    {
        $refs = AssetReference::query()
        ->where('asset_path', $assetPath)
        ->where('asset_container', $containerHandle);
        
        $refs = $lazy ? $refs->lazy() : $refs->get();
            
        return $refs;
    }
    
    public static function findEntries(string $assetPath, string $containerHandle, bool $lazy = false): Collection|LazyCollection
    {
        return self::find($assetPath, $containerHandle, $lazy)
        ->map(fn ($ref) => Entry::find($ref->entry_id));
    }
    
    public static function remove(string $assetPath, string $containerHandle): void
    {
        self::find($assetPath, $containerHandle)
        ->each(fn ($item) => $item->delete());
    }
    
    public static function store(string $assetPath, string $entryId, string $containerHandle): void
    {
        AssetReference::firstOrCreate([
            'entry_id' => $entryId,
            'asset_path' => $assetPath,
            'asset_container' => $containerHandle
        ])
        ->save();
    }
    
    public static function update(string $oldPath, string $newPath, string $container): void
    {
        if ($oldPath === $newPath) {
            return;
        }
        
        $refs = self::find($oldPath, $container);
        
        $refs->each(function ($ref) use ($newPath) {
            $ref->asset_path = $newPath;
            $ref->save();
        });
    }
}