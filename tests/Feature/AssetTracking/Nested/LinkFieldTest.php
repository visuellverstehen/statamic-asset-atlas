<?php

it('tracks replicator nested link field asset references', function () {
    $asset = $this->createTestAsset('test-replicator-link.jpg');
    $asset->save();

    $linkData = 'asset::assets::'.$asset->path();

    $entry = $this->createEntryWithNestedAsset('link_field', $linkData);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);

    $entry->delete();

    expect($asset)->not->toBeTrackedFor($entry);
});
