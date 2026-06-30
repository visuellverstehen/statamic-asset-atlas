<?php

use VV\AssetAtlas\AssetScanner;

/*
 * Cross-type coverage for the TrackAssetReferences subscriber and the scanner's
 * data-swap. The Nested/* suite proves the swap logic in depth, but only for
 * entries. These tests run the same paths for terms and global variables - the
 * other item types the subscriber and scanner handle - via one Pest dataset.
 *
 * Top-level assets are enough: the point is to confirm each item type round-
 * trips through the subscriber (save tracks, delete prunes) and survives the
 * scanner's swap of $item->data() without corruption.
 */
dataset('item types', ['entry', 'term', 'global']);

it('tracks a top-level asset reference when an item is saved', function (string $type) {
    $asset = $this->createAsset("track-{$type}.jpg");

    $item = $this->makeItemWithAsset($type, [$asset->path()]);

    expect($item)->toBeTrackedFor($asset);
})->with('item types');

it('restores the item data after a scan (data-swap invariant)', function (string $type) {
    $asset = $this->createAsset("restore-{$type}.jpg");

    $item = $this->makeItemWithAsset($type, [$asset->path()]);
    $before = $item->data()->all();

    AssetScanner::item($item)
        ->checkOriginal()
        ->addReferences();

    expect($item->data()->all())->toBe($before);
})->with('item types');
