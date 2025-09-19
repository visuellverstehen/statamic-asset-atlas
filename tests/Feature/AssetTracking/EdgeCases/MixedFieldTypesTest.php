<?php

use Illuminate\Support\Facades\DB;
use Statamic\Facades\Entry;

it('tracks assets in mixed field types within same entry', function () {
    $assetForAssetsField = $this->createTestAsset('test-mixed-assets.jpg');
    $assetForBardField = $this->createTestAsset('test-mixed-bard.jpg');
    $assetForAssetsField->save();
    $assetForBardField->save();

    $bardContent = [
        [
            'type' => 'image',
            'attrs' => [
                'src' => 'asset::assets::'.$assetForBardField->path(),
                'alt' => 'Bard image',
            ],
        ],
    ];

    $entry = Entry::make()
        ->collection('pages')
        ->blueprint('page')
        ->slug('test-mixed-fields-'.time())
        ->data([
            'title' => 'Test Mixed Asset Fields',
            'assets_field' => [$assetForAssetsField->path()],
            'bard_field' => $bardContent,
        ]);

    $entry->save();

    expect($entry)->toBeTrackedFor($assetForAssetsField, 1);
    expect($entry)->toBeTrackedFor($assetForBardField, 1);

    // Verify total count for this entry
    $totalReferences = DB::table('asset_atlas')
        ->where('item_id', $entry->id())
        ->count();

    expect($totalReferences)->toBe(2);
});
