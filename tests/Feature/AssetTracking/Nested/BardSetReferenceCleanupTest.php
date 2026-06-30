<?php

use Illuminate\Support\Facades\DB;
use VV\AssetAtlas\AssetScanner;

// See ReplicatorReferenceCleanupTest for why pruning is driven via
// setOriginal()/checkOriginal() rather than through a real save.
it('prunes a bard set asset reference that was removed from a node', function () {
    $asset = $this->createAsset('test-bard-set-cleanup.jpg');

    $entry = $this->createEntryWithBardSetAsset('assets_field', [$asset->path()]);
    expect($entry)->toBeTrackedFor($asset);

    $originalData = $entry->data()->all();

    // The editor deletes the bard set node holding the asset.
    $entry->set('bard_set_field', []);

    AssetScanner::item($entry)
        ->setOriginal($originalData)
        ->checkOriginal()
        ->addReferences();

    $remaining = DB::table('asset_atlas')
        ->where('asset_path', $asset->path())
        ->where('item_id', $entry->id())
        ->count();

    expect($remaining)->toBe(0,
        "Stale reference left behind: '{$asset->path()}' is still tracked for entry '{$entry->id()}' after its bard set node was removed."
    );
});

/*
 * Partial removal: only one of several bard set nodes is deleted. The removed
 * node's asset must be pruned while the surviving node's asset must stay tracked.
 */
it('prunes only the removed bard set node and keeps the surviving node reference', function () {
    $removedAsset = $this->createAsset('test-bard-set-removed.jpg');
    $keptAsset = $this->createAsset('test-bard-set-kept.jpg');

    $entry = $this->createEntryWithBardSetNodes([
        ['assets_field' => [$removedAsset->path()]],
        ['assets_field' => [$keptAsset->path()]],
    ]);

    expect($entry)->toBeTrackedFor($removedAsset)
        ->toBeTrackedFor($keptAsset);

    $originalData = $entry->data()->all();

    $nodes = $entry->get('bard_set_field');
    array_shift($nodes);
    $entry->set('bard_set_field', $nodes);

    AssetScanner::item($entry)
        ->setOriginal($originalData)
        ->checkOriginal()
        ->addReferences();

    expect($entry)->toBeTrackedFor($keptAsset);

    $removedRemaining = DB::table('asset_atlas')
        ->where('asset_path', $removedAsset->path())
        ->where('item_id', $entry->id())
        ->count();

    expect($removedRemaining)->toBe(0,
        "Stale reference left behind for removed node's asset '{$removedAsset->path()}'."
    );
});
