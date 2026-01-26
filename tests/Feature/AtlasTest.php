<?php

use Ramsey\Uuid\Uuid;
use VV\AssetAtlas\AssetAtlas;
use VV\AssetAtlas\AssetReference;

dataset('item_types', [
    'entry' => ['entry', 'findEntries'],
    'term' => ['term', 'findTerms'],
    'global_var' => ['global_var', 'findGlobalVar'],
    'user' => ['user', 'findUsers'],
]);

it('excludes references to non-existent items', function (string $itemType, string $method) {
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

    $exists = \Illuminate\Support\Facades\DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('asset_container', $assetContainer)
        ->where('item_type', $itemType)
        ->where('item_id', $itemId)
        ->exists();

    expect($exists)->toBeTrue();

    $trackedItems = AssetAtlas::$method($assetPath, $assetContainer);

    expect($trackedItems)->toBeEmpty();
})->with('item_types');
