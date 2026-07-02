<?php

use Illuminate\Support\Facades\DB;
use VV\AssetAtlas\AssetScanner;

/*
 * Integrity guards for nested reference pruning. The scanner reads set/row
 * structure from its own $dataToScan snapshot (via the *Children overrides)
 * rather than mutating the live item, so these tests lock in two invariants:
 *
 *   1. The item's data is byte-for-byte unchanged after a scan - for both the
 *      current-data pass and the original-data prune pass. The scanner must
 *      never write back to the item it scans (see ScannerDoesNotMutateItemTest
 *      for the contract-level guard); a regression here would mean it did.
 *
 *   2. The production entry path - which relies on $item->getOriginal() rather
 *      than an explicitly setOriginal() snapshot - actually prunes a removed
 *      nested set's reference. This closes the gap that the per-container
 *      cleanup tests leave open by driving pruning via setOriginal().
 */

it('restores the item data after scanning current replicator data', function () {
    $asset = $this->createAsset('test-restore-replicator.jpg');

    $entry = $this->createEntryWithNestedAsset('assets_field', [$asset->path()]);
    $before = $entry->data()->all();

    AssetScanner::item($entry)->addReferences();

    expect($entry->data()->all())->toBe($before);
});

it('restores the item data after scanning current grid data', function () {
    $asset = $this->createAsset('test-restore-grid.jpg');

    $entry = $this->createEntryWithGridAsset('assets_field', [$asset->path()]);
    $before = $entry->data()->all();

    AssetScanner::item($entry)->addReferences();

    expect($entry->data()->all())->toBe($before);
});

it('restores the item data after scanning current bard set data', function () {
    $asset = $this->createAsset('test-restore-bard.jpg');

    $entry = $this->createEntryWithBardSetAsset('assets_field', [$asset->path()]);
    $before = $entry->data()->all();

    AssetScanner::item($entry)->addReferences();

    expect($entry->data()->all())->toBe($before);
});

it('restores the item data after the original-data prune pass', function () {
    $asset = $this->createAsset('test-restore-original.jpg');

    $entry = $this->createEntryWithNestedAsset('assets_field', [$asset->path()]);
    $originalData = $entry->data()->all();

    // Mutate current data; the prune pass scans the original snapshot, which
    // must not leak back onto the live item.
    $entry->set('replicator_field', []);
    $currentData = $entry->data()->all();

    AssetScanner::item($entry)
        ->setOriginal($originalData)
        ->checkOriginal()
        ->addReferences();

    expect($entry->data()->all())->toBe($currentData);
});

/*
 * Production entry path: Entry::save() syncs the original state, so when the
 * save subscriber runs AssetScanner::item($entry)->checkOriginal()->addReferences()
 * without setOriginal(), getOriginal() falls back to the synced snapshot. This
 * proves that path carries nested set data in a shape the traversal can walk.
 */
it('prunes a removed replicator set reference via the getOriginal fallback', function () {
    $asset = $this->createAsset('test-getoriginal-fallback.jpg');

    // Saving syncs the original state to include the nested asset reference.
    $entry = $this->createEntryWithNestedAsset('assets_field', [$asset->path()]);
    expect($entry)->toBeTrackedFor($asset);

    // Sanity: getOriginal() reflects the saved nested structure.
    expect($entry->getOriginal('replicator_field'))->not->toBeEmpty();

    // Editor deletes the set; current data no longer references the asset.
    $entry->set('replicator_field', []);

    // No setOriginal() here - this is the real entry save subscriber call.
    AssetScanner::item($entry)
        ->checkOriginal()
        ->addReferences();

    $remaining = DB::table('asset_atlas')
        ->where('asset_path', $asset->path())
        ->where('item_id', $entry->id())
        ->count();

    expect($remaining)->toBe(0,
        "Stale reference left behind when pruning relied on getOriginal(): '{$asset->path()}' still tracked for entry '{$entry->id()}'."
    );
});
