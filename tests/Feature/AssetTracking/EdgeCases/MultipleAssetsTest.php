<?php

use Illuminate\Support\Facades\DB;

it('tracks multiple assets in single assets field', function () {
    $asset1 = $this->createAsset('test-multiple-1.jpg');
    $asset2 = $this->createAsset('test-multiple-2.jpg');

    $entry = $this->createEntryWithTopLevelAsset('assets_field', [$asset1->path(), $asset2->path()]);

    expect($entry)->toBeTrackedFor($asset1, 1);
    expect($entry)->toBeTrackedFor($asset2, 1);

    // Verify total count for this entry
    $totalReferences = DB::table('asset_atlas')
        ->where('item_id', $entry->id())
        ->count();

    expect($totalReferences)->toBe(2);
});
