<?php

use Illuminate\Support\Facades\DB;
use VV\AssetAtlas\AssetScanner;

// See ReplicatorReferenceCleanupTest for why pruning is driven via
// setOriginal()/checkOriginal() rather than through a real save.
it('prunes a grid asset reference that was removed from a row', function () {
    $asset = $this->createAsset('test-grid-cleanup.jpg');

    $entry = $this->createEntryWithGridAsset('assets_field', [$asset->path()]);
    expect($entry)->toBeTrackedFor($asset);

    $originalData = $entry->data()->all();

    // The editor deletes the grid row holding the asset.
    $entry->set('grid_field', []);

    AssetScanner::item($entry)
        ->setOriginal($originalData)
        ->checkOriginal()
        ->addReferences();

    $remaining = DB::table('asset_atlas')
        ->where('asset_path', $asset->path())
        ->where('item_id', $entry->id())
        ->count();

    expect($remaining)->toBe(0,
        "Stale reference left behind: '{$asset->path()}' is still tracked for entry '{$entry->id()}' after its grid row was removed."
    );
});

/*
 * Partial removal: only one of several grid rows is deleted. The removed row's
 * asset must be pruned while the surviving row's asset must stay tracked.
 */
it('prunes only the removed grid row and keeps the surviving row reference', function () {
    $removedAsset = $this->createAsset('test-grid-removed.jpg');
    $keptAsset = $this->createAsset('test-grid-kept.jpg');

    $entry = $this->createEntryWithGridRows([
        ['assets_field' => [$removedAsset->path()]],
        ['assets_field' => [$keptAsset->path()]],
    ]);

    expect($entry)
        ->toBeTrackedFor($removedAsset)
        ->toBeTrackedFor($keptAsset);

    $originalData = $entry->data()->all();

    $rows = $entry->get('grid_field');
    array_shift($rows);
    $entry->set('grid_field', $rows);

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
        "Stale reference left behind for removed row's asset '{$removedAsset->path()}'."
    );
});
