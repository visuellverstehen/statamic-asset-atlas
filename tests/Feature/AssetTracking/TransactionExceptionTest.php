<?php

use Illuminate\Support\Facades\DB;
use VV\AssetAtlas\Exceptions\AssetAtlasTransactionException;

it('throws AssetAtlasTransactionException when the transaction fails on save', function () {
    $asset = $this->createAsset('test-transaction-fail.jpg');

    DB::shouldReceive('transaction')
        ->once()
        ->andThrow(new RuntimeException('DB says no'));

    $this->createEntryWithTopLevelAsset('assets_field', [$asset->path()]);
})->throws(AssetAtlasTransactionException::class);

it('includes item context in the transaction exception', function () {
    $asset = $this->createAsset('test-transaction-context.jpg');

    DB::shouldReceive('transaction')
        ->once()
        ->andThrow(new RuntimeException('DB says no'));

    try {
        $this->createEntryWithTopLevelAsset('assets_field', [$asset->path()]);
    } catch (AssetAtlasTransactionException $e) {
        expect($e->itemType)->toBe('entry');
        expect($e->itemContext)->toBe('pages');
        expect($e->itemId)->not->toBeEmpty();
        expect($e->getMessage())->toContain('entry');
        expect($e->getPrevious())->toBeInstanceOf(RuntimeException::class);
        expect($e->getPrevious()->getMessage())->toBe('DB says no');

        return;
    }

    $this->fail('Expected AssetAtlasTransactionException was not thrown.');
});
