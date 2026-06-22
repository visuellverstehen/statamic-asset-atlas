<?php

use Illuminate\Support\Facades\DB;
use VV\AssetAtlas\AssetScanner;

/*
 * Regression test for stale references left inside replicator/grid/bard sets.
 *
 * When an item is saved, the scanner re-scans its *original* data and prunes
 * references that no longer exist in the current data. That pruning is exercised
 * directly here via setOriginal()/checkOriginal() - the same API the GlobalVars
 * subscriber uses - rather than through a real save. The testbench harness syncs
 * an entry's original state *before* EntrySaved fires (real Statamic syncs it
 * after), so an event-driven test cannot observe the original data the pruning
 * relies on.
 */
it('prunes a nested replicator asset reference that was removed from a set', function () {
    $asset = $this->createAsset('test-replicator-cleanup.jpg');

    // Entry that once held the asset inside a replicator set -> reference tracked.
    $entry = $this->createEntryWithNestedAsset('assets_field', [$asset->path()]);
    expect($entry)->toBeTrackedFor($asset);

    $originalData = $entry->data()->all();

    // The editor deletes the set; the current data no longer references the asset.
    $entry->set('replicator_field', []);

    // Same call the save subscriber makes: scan current data, then diff against
    // the original to drop references that disappeared.
    AssetScanner::item($entry)
        ->setOriginal($originalData)
        ->checkOriginal()
        ->addReferences();

    $remaining = DB::table('asset_atlas')
        ->where('asset_path', $asset->path())
        ->where('item_id', $entry->id())
        ->count();

    expect($remaining)->toBe(0,
        "Stale reference left behind: '{$asset->path()}' is still tracked for entry '{$entry->id()}' after its replicator set was removed."
    );
});
