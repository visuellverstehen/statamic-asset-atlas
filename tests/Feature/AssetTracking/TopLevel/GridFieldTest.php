<?php

it('tracks assets inside grid field rows', function () {
    $asset = $this->createAsset('grid-asset-1.jpg');
    $asset2 = $this->createAsset('grid-asset-2.jpg');

    $entry = $this->createEntryWithTopLevelAsset('grid_field', [
        ['grid_assets' => [$asset->path()]],
        ['grid_assets' => [$asset2->path()]],
    ]);

    expect($entry)->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    // Update: remove one asset from the grid
    $entry = clone $entry;
    $entry->set('grid_field', [
        ['grid_assets' => [$asset2->path()]],
    ]);
    $entry->save();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    // Delete the entry
    $entry->delete();

    expect($entry)->not->toBeTrackedFor($asset2);
});

it('tracks link field assets inside grid field rows', function () {
    $asset = $this->createAsset('grid-link-asset.jpg');

    $entry = $this->createEntryWithTopLevelAsset('grid_field', [
        ['grid_link' => 'asset::assets::'.$asset->path()],
    ]);

    expect($entry)->toBeTrackedFor($asset);

    // Update: change the link
    $asset2 = $this->createAsset('grid-link-asset-2.jpg');

    $entry = clone $entry;
    $entry->set('grid_field', [
        ['grid_link' => 'asset::assets::'.$asset2->path()],
    ]);
    $entry->save();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);
});
