<?php

it('tracks replicator nested assets field references', function () {
    $asset = $this->createAsset('test-replicator-assets.jpg');
    $asset->save();

    $entry = $this->createEntryWithNestedAsset('assets_field', [$asset->path()]);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});
