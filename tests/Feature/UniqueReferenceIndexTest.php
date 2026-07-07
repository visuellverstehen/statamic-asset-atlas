<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use VV\AssetAtlas\AssetAtlas;
use VV\AssetAtlas\AssetReference;

it('stores duplicate asset references only once', function () {
    $assetPath = 'images/hero.jpg';
    $assetContainer = 'assets';
    $itemId = Uuid::uuid4()->toString();
    $itemType = 'entry';

    AssetAtlas::store($assetPath, $assetContainer, $itemId, $itemType);
    AssetAtlas::store($assetPath, $assetContainer, $itemId, $itemType);

    $count = AssetReference::query()
        ->where('asset_path', $assetPath)
        ->where('asset_container', $assetContainer)
        ->where('item_id', $itemId)
        ->where('item_type', $itemType)
        ->count();

    expect($count)->toBe(1);
});

it('rejects duplicate asset references at the database level', function () {
    $reference = [
        'asset_path' => 'images/hero.jpg',
        'asset_container' => 'assets',
        'item_id' => Uuid::uuid4()->toString(),
        'item_type' => 'entry',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('asset_atlas')->insert($reference);

    expect(fn () => DB::table('asset_atlas')->insert($reference))
        ->toThrow(QueryException::class);
});
