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
