<?php

use Illuminate\Support\Facades\Schema;

test('asset atlas table exists', function () {
    expect(Schema::hasTable('asset_atlas'))->toBeTrue();
});

test('asset atlas table has correct columns', function () {
    expect(Schema::hasColumns('asset_atlas', [
        'id',
        'asset_path',
        'asset_container',
        'item_id',
        'item_type',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
