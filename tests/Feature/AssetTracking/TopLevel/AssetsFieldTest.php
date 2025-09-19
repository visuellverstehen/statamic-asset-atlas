<?php

it('tracks top-level assets field references', function () {
    $asset = $this->createTestAsset('test-assets-field.jpg');
    $asset->save();

    $entry = $this->createEntryWithTopLevelAsset('assets_field', [$asset->path()]);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});
