<?php

it('tracks replicator nested bard field asset references', function () {
    $asset = $this->createAsset('test-replicator-bard.jpg');
    $asset->save();

    $bardContent = [
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Text before image'],
            ],
        ],
        [
            'type' => 'image',
            'attrs' => [
                'src' => 'asset::assets::'.$asset->path(),
                'alt' => null,
            ],
        ],
    ];

    $entry = $this->createEntryWithNestedAsset('bard_field', $bardContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});
