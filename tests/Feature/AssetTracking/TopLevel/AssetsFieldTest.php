<?php

it('tracks top-level assets field references', function () {
    $asset = $this->createAsset('test-assets-field.jpg');

    $entry = $this->createEntryWithTopLevelAsset('assets_field', [$asset->path()]);

    expect($entry)->toBeTrackedFor($asset);

    // (cloning the entry fixes an issue with the stache in tests)
    $entry = clone $entry;

    $asset2 = $this->createAsset('test-assets-field-2.jpg');

    $entry->set('assets_field', [$asset2->path()]);
    $entry->save();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    // test with multiple values
    $entry = clone $entry;

    $entry->set('assets_field', [$asset->path(), $asset2->path()]);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    // test deleting the asset
    $asset->delete();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    // test deleting the entry
    $entry->delete();

    expect($entry)->not->toBeTrackedFor($asset2);
});
