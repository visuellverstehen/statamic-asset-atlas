<?php

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use VV\AssetAtlas\AssetAtlas;
use VV\AssetAtlas\AssetAtlasException;
use VV\AssetAtlas\AssetReference;

dataset('item_types', [
    'entry' => ['entry', 'findEntries'],
    'term' => ['term', 'findTerms'],
    'global_var' => ['global_var', 'findGlobalVar'],
    'user' => ['user', 'findUsers'],
]);

// ============================================
// Resolution Tests (filtering non-existent items)
// ============================================

it('excludes references to non-existent items when checking dedicated item types', function (string $itemType, string $method) {
    $assetPath = 'foo/bar/foobar.jpg';
    $assetContainer = 'assets';

    $itemId = match ($itemType) {
        'entry' => Uuid::uuid4()->toString(),
        'term' => 'test_taxonomy::test_term',
        'global_var' => 'test_globalvar',
        'user' => Uuid::uuid4()->toString(),
    };

    AssetReference::create([
        'asset_path' => $assetPath,
        'asset_container' => $assetContainer,
        'item_id' => $itemId,
        'item_type' => $itemType,
    ]);

    $exists = DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('asset_container', $assetContainer)
        ->where('item_type', $itemType)
        ->where('item_id', $itemId)
        ->exists();

    expect($exists)->toBeTrue();

    // Check for specific references without resolving them
    $references = AssetAtlas::find($assetPath, $assetContainer, $itemType);
    expect($references)->not()->toBeEmpty();

    // Check for specific references and resolve them, if possible
    $trackedItems = AssetAtlas::$method($assetPath, $assetContainer);
    expect($trackedItems)->toBeEmpty();
})->with('item_types');

it('excludes references to non-existent items when checking for unspecified items', function () {
    $assetPath = 'unspecified/foobar.png';
    $assetContainer = 'assets';

    $itemIds = [
        'entry' => Uuid::uuid4()->toString(),
        'term' => 'test_taxonomy::test_term',
        'global_var' => 'test_globalvar',
        'user' => Uuid::uuid4()->toString(),
    ];

    foreach ($itemIds as $itemType => $itemId) {
        AssetReference::create([
            'asset_path' => $assetPath,
            'asset_container' => $assetContainer,
            'item_id' => $itemId,
            'item_type' => $itemType,
        ]);

        $exists = DB::table('asset_atlas')
            ->where('asset_path', $assetPath)
            ->where('asset_container', $assetContainer)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->exists();

        expect($exists)->toBeTrue();
    }

    // Check for references without resolving them
    $references = AssetAtlas::find($assetPath, $assetContainer);
    expect($references)->not()->toBeEmpty();

    // Check for references and resolve them, if possible
    $trackedItems = AssetAtlas::findAll($assetPath, $assetContainer);
    expect($trackedItems)->toBeEmpty();
});

// ============================================
// Store Tests
// ============================================

it('stores asset references', function () {
    $assetPath = 'store-test/image.jpg';
    $container = 'assets';
    $itemId = Uuid::uuid4()->toString();

    AssetAtlas::store($assetPath, $container, $itemId, 'entry');

    $exists = DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('asset_container', $container)
        ->where('item_id', $itemId)
        ->where('item_type', 'entry')
        ->exists();

    expect($exists)->toBeTrue();
});

it('stores entry references with convenience method', function () {
    $assetPath = 'store-entry-test/image.jpg';
    $container = 'assets';
    $entryId = Uuid::uuid4()->toString();

    AssetAtlas::storeEntry($assetPath, $container, $entryId);

    $exists = DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('asset_container', $container)
        ->where('item_id', $entryId)
        ->where('item_type', 'entry')
        ->exists();

    expect($exists)->toBeTrue();
});

it('throws exception for invalid item type', function () {
    AssetAtlas::store('test.jpg', 'assets', 'some-id', 'invalid_type');
})->throws(AssetAtlasException::class, 'Invalid item type: invalid_type');

it('does not create duplicate references', function () {
    $assetPath = 'duplicate-test/image.jpg';
    $container = 'assets';
    $itemId = Uuid::uuid4()->toString();

    AssetAtlas::store($assetPath, $container, $itemId, 'entry');
    AssetAtlas::store($assetPath, $container, $itemId, 'entry');

    $count = DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('asset_container', $container)
        ->where('item_id', $itemId)
        ->count();

    expect($count)->toBe(1);
});

// ============================================
// Remove Tests
// ============================================

it('removes a single asset reference', function () {
    $assetPath = 'remove-test/image.jpg';
    $container = 'assets';
    $itemId = Uuid::uuid4()->toString();

    AssetAtlas::store($assetPath, $container, $itemId, 'entry');

    AssetAtlas::remove($assetPath, $container, $itemId);

    $exists = DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('asset_container', $container)
        ->where('item_id', $itemId)
        ->exists();

    expect($exists)->toBeFalse();
});

it('removes all references for an asset', function () {
    $assetPath = 'remove-all-asset/image.jpg';
    $container = 'assets';

    AssetAtlas::store($assetPath, $container, Uuid::uuid4()->toString(), 'entry');
    AssetAtlas::store($assetPath, $container, Uuid::uuid4()->toString(), 'entry');
    AssetAtlas::store($assetPath, $container, 'test_globalvar', 'global_var');

    AssetAtlas::removeAllByAsset($assetPath, $container);

    $count = DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('asset_container', $container)
        ->count();

    expect($count)->toBe(0);
});

it('removes all references for an item', function () {
    $itemId = Uuid::uuid4()->toString();

    AssetAtlas::store('asset1.jpg', 'assets', $itemId, 'entry');
    AssetAtlas::store('asset2.jpg', 'assets', $itemId, 'entry');
    AssetAtlas::store('asset3.jpg', 'assets', $itemId, 'entry');

    AssetAtlas::removeAllByItem($itemId);

    $count = DB::table('asset_atlas')
        ->where('item_id', $itemId)
        ->count();

    expect($count)->toBe(0);
});

// ============================================
// Update Tests
// ============================================

it('updates asset path on rename', function () {
    $oldPath = 'update-test/old-name.jpg';
    $newPath = 'update-test/new-name.jpg';
    $container = 'assets';
    $itemId = Uuid::uuid4()->toString();

    AssetAtlas::store($oldPath, $container, $itemId, 'entry');

    AssetAtlas::update($oldPath, $newPath, $container);

    $oldExists = DB::table('asset_atlas')
        ->where('asset_path', $oldPath)
        ->where('item_id', $itemId)
        ->exists();

    $newExists = DB::table('asset_atlas')
        ->where('asset_path', $newPath)
        ->where('item_id', $itemId)
        ->exists();

    expect($oldExists)->toBeFalse();
    expect($newExists)->toBeTrue();
});

it('does nothing when old and new paths are the same', function () {
    $assetPath = 'same-path-test/image.jpg';
    $container = 'assets';
    $itemId = Uuid::uuid4()->toString();

    AssetAtlas::store($assetPath, $container, $itemId, 'entry');

    AssetAtlas::update($assetPath, $assetPath, $container);

    $count = DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('item_id', $itemId)
        ->count();

    expect($count)->toBe(1);
});

it('updates multiple references for the same asset', function () {
    $oldPath = 'bulk-update/asset.jpg';
    $newPath = 'bulk-update/renamed.jpg';
    $container = 'assets';

    $item1 = Uuid::uuid4()->toString();
    $item2 = Uuid::uuid4()->toString();
    $item3 = Uuid::uuid4()->toString();

    AssetReference::create(['asset_path' => $oldPath, 'asset_container' => $container, 'item_id' => $item1, 'item_type' => 'entry']);
    AssetReference::create(['asset_path' => $oldPath, 'asset_container' => $container, 'item_id' => $item2, 'item_type' => 'entry']);
    AssetReference::create(['asset_path' => $oldPath, 'asset_container' => $container, 'item_id' => $item3, 'item_type' => 'global_var']);

    AssetAtlas::update($oldPath, $newPath, $container);

    $oldCount = DB::table('asset_atlas')->where('asset_path', $oldPath)->count();
    $newCount = DB::table('asset_atlas')->where('asset_path', $newPath)->count();

    expect($oldCount)->toBe(0);
    expect($newCount)->toBe(3);
});
