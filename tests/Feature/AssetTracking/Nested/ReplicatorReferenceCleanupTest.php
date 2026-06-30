<?php

use Illuminate\Support\Facades\DB;
use VV\AssetAtlas\AssetScanner;

/*
 * Regression test for stale references left inside replicator/grid/bard sets.
 *
 * When an item is saved, the scanner re-scans its *original* data and prunes
 * references that no longer exist in the current data. In real Statamic,
 * Entry::save() dispatches EntrySaved *before* calling syncOriginal()
 * (Entry.php:433 then :446), so during the save subscriber getOriginal()
 * returns the previous save's snapshot - the pre-edit data the prune pass diffs
 * against. That fallback is proven by DataSwapRegressionTest.
 *
 * Here we drive the prune pass directly via setOriginal()/checkOriginal() - the
 * same API the GlobalVars subscriber uses - rather than through a second real
 * save. This isolates the nested-prune logic from the two-save dance and the
 * subscriber/transaction/Stache wiring, and lets us feed an exact snapshot.
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

/*
 * Partial removal: only one of several replicator sets is deleted. The removed
 * set's asset must be pruned while the surviving set's asset must stay tracked.
 * Guards against over-pruning and against a broken nested traversal that would
 * skip surviving sets.
 */
it('prunes only the removed replicator set and keeps the surviving set reference', function () {
    $removedAsset = $this->createAsset('test-replicator-removed.jpg');
    $keptAsset = $this->createAsset('test-replicator-kept.jpg');

    $entry = $this->createEntryWithReplicatorSets([
        ['assets_field' => [$removedAsset->path()]],
        ['assets_field' => [$keptAsset->path()]],
    ]);

    expect($entry)
        ->toBeTrackedFor($removedAsset)
        ->toBeTrackedFor($keptAsset);

    $originalData = $entry->data()->all();

    // Drop the first set; keep the second set verbatim.
    $sets = $entry->get('replicator_field');
    array_shift($sets);
    $entry->set('replicator_field', $sets);

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
        "Stale reference left behind for removed set's asset '{$removedAsset->path()}'."
    );
});
