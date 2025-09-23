<?php

it('tracks top-level link field asset references', function () {
    $asset = $this->createAsset('test-link-field.jpg');

    $linkData = 'asset::assets::'.$asset->path();

    $entry = $this->createEntryWithTopLevelAsset('link_field', $linkData);

    expect($entry)->toBeTrackedFor($asset);

    $asset2 = $this->createAsset('test-link-field-2.jpg');

    // test updating data
    $entry = clone $entry;
    $updatedLinkData = 'asset::assets::'.$asset2->path();

    $entry->set('link_field', $updatedLinkData);
    $entry->save();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    // test deleting the asset
    $asset->delete();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    // test deleting the entry
    $entry->delete();

    expect($entry)->not->toBeTrackedFor($asset2);
});
