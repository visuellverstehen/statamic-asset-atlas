<?php

it('tracks assets inside grid nested in replicator', function () {
    $asset = $this->createAsset('nested-grid-asset.jpg');

    $replicatorData = [
        [
            'id' => uniqid(),
            'type' => 'new_set',
            'enabled' => true,
            'nested_grid' => [
                ['grid_assets' => [$asset->path()]],
            ],
        ],
    ];

    $entry = $this->createEntryWithTopLevelAsset('replicator_field', $replicatorData);

    expect($entry)->toBeTrackedFor($asset);

    // Delete the entry
    $entry->delete();

    expect($entry)->not->toBeTrackedFor($asset);
});
