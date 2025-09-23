<?php

it('tracks replicator nested assets field references', function () {
    $asset = $this->createAsset('test-replicator-assets.jpg');

    $entry = $this->createEntryWithNestedAsset('assets_field', [$asset->path()]);

    expect($entry)->toBeTrackedFor($asset);
});
