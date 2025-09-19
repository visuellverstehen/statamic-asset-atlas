<?php

it('tracks top-level assets field references', function () {
    $asset = $this->createAsset('test-assets-field.jpg');
    $asset->save();

    $entry = $this->createEntryWithTopLevelAsset('assets_field', [$asset->path()]);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);

    $asset2 = $this->createAsset('test-assets-field-2.jpg');
    $asset2->save();

    $entry->set('assets_field', [$asset->path(), $asset2->path()]);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $asset->delete();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $entry->delete();

    expect($entry)->not->toBeTrackedFor($asset2);
});
