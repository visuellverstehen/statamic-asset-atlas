<?php

namespace VV\AssetAtlas;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalVariables;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use VV\AssetAtlas\Exceptions\AssetAtlasException;

class Atlas
{
    protected $lazyThreshold = 500;

    protected $itemTypes = ['entry', 'working_copy', 'term', 'global_var', 'user'];

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
        return $this
            ->find($assetPath, $containerHandle)
            ->map(function ($ref) {
                return match ($ref->item_type) {
                    // A working copy is updated together with its entry.
                    'entry', 'working_copy' => Entry::find($ref->item_id),
                    'global_var' => GlobalVariables::find($ref->item_id),
                    'term' => Term::find($ref->item_id),
                    'user' => User::find($ref->item_id),
                };
            })
            ->filter()
            ->unique(fn ($item) => get_class($item).':'.$item->id());
    }

    public function findEntries(string $assetPath, string $containerHandle): Collection|LazyCollection
    {
        return $this
            ->find($assetPath, $containerHandle, 'entry')
            ->map(fn ($ref) => Entry::find($ref->item_id))
            ->filter();
    }

    public function findGlobalVar(string $assetPath, string $containerHandle): Collection|LazyCollection
    {
        return $this
            ->find($assetPath, $containerHandle, 'global_var')
            ->map(fn ($ref) => GlobalVariables::find($ref->item_id))
            ->filter();
    }

    public function findTerms(string $assetPath, string $containerHandle): Collection|LazyCollection
    {
        return $this
            ->find($assetPath, $containerHandle, 'term')
            ->map(fn ($ref) => Term::find($ref->item_id))
            ->filter();
    }

    public function findUsers(string $assetPath, string $containerHandle): Collection|LazyCollection
    {
        return $this
            ->find($assetPath, $containerHandle, 'user')
            ->map(fn ($ref) => User::find($ref->item_id))
            ->filter();
    }

    public function remove(string $assetPath, string $containerHandle, string $itemId, ?string $itemType = null)
    {
        AssetReference::query()
            ->where('asset_path', $assetPath)
            ->where('asset_container', $containerHandle)
            ->where('item_id', $itemId)
            ->when($itemType, fn ($query) => $query->where('item_type', $itemType))
            ->delete();
    }

    public function removeAllByAsset(string $assetPath, string $containerHandle): void
    {
        AssetReference::query()
            ->where('asset_path', $assetPath)
            ->where('asset_container', $containerHandle)
            ->delete();
    }

    public function removeAllByItem(string $itemId, ?string $itemType = null): void
    {
        AssetReference::query()
            ->where('item_id', $itemId)
            ->when($itemType, fn ($query) => $query->where('item_type', $itemType))
            ->delete();
    }

    public function store(string $assetPath, string $containerHandle, string $itemId, string $itemType): void
    {
        if (! in_array($itemType, $this->itemTypes)) {
            throw new AssetAtlasException('Invalid item type: '.$itemType);
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

        // One statement instead of paging: renamed rows would drop out of the next page.
        DB::transaction(function () use ($oldPath, $newPath, $container) {
            // An item can already reference the new path, e.g. after replacing an asset with one it also uses.
            // Selected first: MySQL rejects a DELETE whose subquery reads the same table (error 1093).
            $duplicateIds = AssetReference::query()
                ->where('asset_path', $oldPath)
                ->where('asset_container', $container)
                ->whereExists(fn ($query) => $query
                    ->from('asset_atlas as existing')
                    ->where('existing.asset_path', $newPath)
                    ->where('existing.asset_container', $container)
                    ->whereColumn('existing.item_id', 'asset_atlas.item_id')
                    ->whereColumn('existing.item_type', 'asset_atlas.item_type'))
                ->pluck('id');

            $duplicateIds->chunk(1000)->each(fn ($ids) => AssetReference::whereKey($ids)->delete());

            AssetReference::query()
                ->where('asset_path', $oldPath)
                ->where('asset_container', $container)
                ->update(['asset_path' => $newPath]);
        });
    }
}
