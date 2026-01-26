<?php

use Ramsey\Uuid\Uuid;
use VV\AssetAtlas\AssetAtlas;
use VV\AssetAtlas\AssetReference;

it('doesn\'t return null values', function () {
    $entryId = Uuid::uuid4()->toString();
    $assetPath = 'foo/bar/foobar.jpg';
    $assetContainer = 'assets';

    $ref = AssetReference::create([
        'asset_path' => $assetPath,
        'asset_container' => $assetContainer,
        'item_id' => $entryId,
        'item_type' => 'entry',
    ]);

    $exists = \Illuminate\Support\Facades\DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('asset_container', $assetContainer)
        ->where('item_id', $entryId)
        ->exists();

    expect($exists)->toBeTrue();

    $trackedEntries = AssetAtlas::findEntries($assetPath, $assetContainer);

    expect($trackedEntries)->toBeEmpty();
});
