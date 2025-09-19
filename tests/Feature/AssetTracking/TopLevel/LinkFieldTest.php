<?php

it('tracks top-level link field asset references', function () {
    $asset = $this->createAsset('test-link-field.jpg');
    $asset->save();

    $linkData = 'asset::assets::'.$asset->path();

    $entry = $this->createEntryWithTopLevelAsset('link_field', $linkData);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);

    $asset2 = $this->createAsset('test-link-field-2.jpg');
    $asset2->save();

    $updatedLinkData = 'asset::assets::'.$asset2->path();

    $entry->set('link_field', $updatedLinkData);
    $entry->save();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $asset->delete();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $entry->delete();

    expect($entry)->not->toBeTrackedFor($asset2);
})->skip();
