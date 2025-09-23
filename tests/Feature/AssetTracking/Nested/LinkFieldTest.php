<?php

it('tracks replicator nested link field asset references', function () {
    $asset = $this->createAsset('test-replicator-link.jpg');

    $linkData = 'asset::assets::'.$asset->path();

    $entry = $this->createEntryWithNestedAsset('link_field', $linkData);

    expect($entry)->toBeTrackedFor($asset);

    $entry->delete();

    expect($asset)->not->toBeTrackedFor($entry);
});
