<?php

use VV\AssetAtlas\AssetScanner;

/*
 * Cross-type coverage for the TrackAssetReferences subscriber and the scanner.
 * The Nested/* suite proves the traversal in depth, but only for entries. These
 * tests run the same paths for terms and global variables - the other item
 * types the subscriber and scanner handle - via one Pest dataset.
 *
 * Top-level assets are enough: the point is to confirm each item type round-
 * trips through the subscriber (save tracks, delete prunes) and is left
 * untouched by the scan.
 */
dataset('item types', ['entry', 'term', 'global']);

it('tracks a top-level asset reference when an item is saved', function (string $type) {
    $asset = $this->createAsset("track-{$type}.jpg");

    $item = $this->makeItemWithAsset($type, [$asset->path()]);

    expect($item)->toBeTrackedFor($asset);
})->with('item types');

it('leaves the item data untouched after a scan', function (string $type) {
    $asset = $this->createAsset("restore-{$type}.jpg");

    $item = $this->makeItemWithAsset($type, [$asset->path()]);
    $before = $item->data()->all();

    AssetScanner::item($item)
        ->checkOriginal()
        ->addReferences();

    expect($item->data()->all())->toBe($before);
})->with('item types');
