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
