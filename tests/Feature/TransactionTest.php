<?php

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use VV\AssetAtlas\AssetAtlas;

it('rolls back reference creation on failed save', function () {
    $assetPath = 'rollback-test/image.jpg';
    $container = 'assets';
    $itemId = Uuid::uuid4()->toString();

    $initialCount = DB::table('asset_atlas')->count();

    try {
        DB::transaction(function () use ($assetPath, $container, $itemId) {
            // Store the reference
            AssetAtlas::store($assetPath, $container, $itemId, 'entry');

            // Verify it was stored within the transaction
            $inTransactionCount = DB::table('asset_atlas')
                ->where('item_id', $itemId)
                ->count();
            expect($inTransactionCount)->toBe(1);

            // Simulate a failure
            throw new Exception('Simulated failure');
        });
    } catch (Exception $e) {
        // Expected
    }

    // Reference should be rolled back
    $count = DB::table('asset_atlas')
        ->where('item_id', $itemId)
        ->count();

    expect($count)->toBe(0);
    expect(DB::table('asset_atlas')->count())->toBe($initialCount);
});

it('rolls back reference removal on failed operation', function () {
    $assetPath = 'rollback-remove/image.jpg';
    $container = 'assets';
    $itemId = Uuid::uuid4()->toString();

    // Store reference first
    AssetAtlas::store($assetPath, $container, $itemId, 'entry');
    $initialCount = DB::table('asset_atlas')->count();

    try {
        DB::transaction(function () use ($assetPath, $container, $itemId) {
            // Remove the reference
            AssetAtlas::remove($assetPath, $container, $itemId);

            // Verify it was removed within the transaction
            $inTransactionCount = DB::table('asset_atlas')
                ->where('item_id', $itemId)
                ->count();
            expect($inTransactionCount)->toBe(0);

            // Simulate a failure
            throw new Exception('Simulated failure');
        });
    } catch (Exception $e) {
        // Expected
    }

    // Reference should be restored (rollback)
    $count = DB::table('asset_atlas')
        ->where('item_id', $itemId)
        ->count();

    expect($count)->toBe(1);
    expect(DB::table('asset_atlas')->count())->toBe($initialCount);
});

it('rolls back path update on failed operation', function () {
    $oldPath = 'update-rollback/old.jpg';
    $newPath = 'update-rollback/new.jpg';
    $container = 'assets';
    $itemId = Uuid::uuid4()->toString();

    // Store reference with old path
    AssetAtlas::store($oldPath, $container, $itemId, 'entry');

    try {
        DB::transaction(function () use ($oldPath, $newPath, $container, $itemId) {
            // Update the path
            AssetAtlas::update($oldPath, $newPath, $container);

            // Verify it was updated within the transaction
            $newExists = DB::table('asset_atlas')
                ->where('asset_path', $newPath)
                ->where('item_id', $itemId)
                ->exists();
            expect($newExists)->toBeTrue();

            // Simulate a failure
            throw new Exception('Simulated failure');
        });
    } catch (Exception $e) {
        // Expected
    }

    // Old path should be restored
    $oldExists = DB::table('asset_atlas')
        ->where('asset_path', $oldPath)
        ->where('item_id', $itemId)
        ->exists();

    $newExists = DB::table('asset_atlas')
        ->where('asset_path', $newPath)
        ->where('item_id', $itemId)
        ->exists();

    expect($oldExists)->toBeTrue();
    expect($newExists)->toBeFalse();
});

it('commits reference creation on successful transaction', function () {
    $assetPath = 'commit-test/image.jpg';
    $container = 'assets';
    $itemId = Uuid::uuid4()->toString();

    DB::transaction(function () use ($assetPath, $container, $itemId) {
        AssetAtlas::store($assetPath, $container, $itemId, 'entry');
    });

    $exists = DB::table('asset_atlas')
        ->where('asset_path', $assetPath)
        ->where('item_id', $itemId)
        ->exists();

    expect($exists)->toBeTrue();
});

it('handles multiple operations in single transaction', function () {
    $container = 'assets';
    $item1 = Uuid::uuid4()->toString();
    $item2 = Uuid::uuid4()->toString();

    DB::transaction(function () use ($container, $item1, $item2) {
        AssetAtlas::store('multi-1.jpg', $container, $item1, 'entry');
        AssetAtlas::store('multi-2.jpg', $container, $item2, 'entry');
    });

    $count = DB::table('asset_atlas')
        ->whereIn('item_id', [$item1, $item2])
        ->count();

    expect($count)->toBe(2);
});

it('rolls back all operations in failed transaction', function () {
    $container = 'assets';
    $item1 = Uuid::uuid4()->toString();
    $item2 = Uuid::uuid4()->toString();
    $initialCount = DB::table('asset_atlas')->count();

    try {
        DB::transaction(function () use ($container, $item1, $item2) {
            AssetAtlas::store('fail-1.jpg', $container, $item1, 'entry');
            AssetAtlas::store('fail-2.jpg', $container, $item2, 'entry');

            // Both should exist within transaction
            $inTransactionCount = DB::table('asset_atlas')
                ->whereIn('item_id', [$item1, $item2])
                ->count();
            expect($inTransactionCount)->toBe(2);

            throw new Exception('Simulated failure');
        });
    } catch (Exception $e) {
        // Expected
    }

    // Neither should exist after rollback
    $count = DB::table('asset_atlas')
        ->whereIn('item_id', [$item1, $item2])
        ->count();

    expect($count)->toBe(0);
    expect(DB::table('asset_atlas')->count())->toBe($initialCount);
});
