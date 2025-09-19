<?php

it('tracks top-level link field asset references', function () {
    $asset = $this->createTestAsset('test-link-field.jpg');
    $asset->save();

    $linkData = 'asset::assets::'.$asset->path();

    $entry = $this->createEntryWithTopLevelAsset('link_field', $linkData);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});
