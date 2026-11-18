<?php

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use VV\AssetAtlas\AssetAtlas;
use VV\AssetAtlas\AssetReference;

dataset('item_types', [
    'entry' => ['entry', 'findEntries'],
    'term' => ['term', 'findTerms'],
    'global_var' => ['global_var', 'findGlobalVar'],
    'user' => ['user', 'findUsers'],
]);

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

it('moves references onto a path the item already references', function () {
    AssetAtlas::store('old.jpg', 'assets', 'item', 'working_copy');
    AssetAtlas::store('new.jpg', 'assets', 'item', 'working_copy');
    AssetAtlas::store('old.jpg', 'assets', 'other', 'entry');

    AssetAtlas::update('old.jpg', 'new.jpg', 'assets');

    expect(DB::table('asset_atlas')->orderBy('item_id')->get(['asset_path', 'item_id'])->map(fn ($row) => (array) $row)->all())
        ->toBe([
            ['asset_path' => 'new.jpg', 'item_id' => 'item'],
            ['asset_path' => 'new.jpg', 'item_id' => 'other'],
        ]);
});

it('moves every reference of an asset used more than the lazy threshold', function () {
    DB::table('asset_atlas')->insert(collect(range(1, 1001))->map(fn ($i) => [
        'asset_path' => 'old.jpg', 'asset_container' => 'assets', 'item_id' => "item-$i", 'item_type' => 'entry',
    ])->all());

    AssetAtlas::update('old.jpg', 'new.jpg', 'assets');

    expect(DB::table('asset_atlas')->where('asset_path', 'old.jpg')->count())->toBe(0);
});
