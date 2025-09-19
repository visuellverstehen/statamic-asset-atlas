<?php

it('tracks top-level bard field asset references', function () {
    $asset = $this->createTestAsset('test-bard-field.jpg');
    $asset->save();

    $bardContent = [
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Here is an image: '],
            ],
        ],
        [
            'type' => 'image',
            'attrs' => [
                'src' => 'asset::assets::'.$asset->path(),
                'alt' => 'Test image',
            ],
        ],
    ];

    $entry = $this->createEntryWithTopLevelAsset('bard_field', $bardContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});

it('tracks top-level bard field with HTML asset references', function () {
    $asset = $this->createTestAsset('test-bard-html-field.jpg');
    $asset->save();

    $bardContent = [
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Here is an image: '],
            ],
        ],
        [
            'type' => 'image',
            'attrs' => [
                'src' => 'asset::assets::'.$asset->path(),
                'alt' => 'Test image with HTML',
            ],
        ],
    ];

    $entry = $this->createEntryWithTopLevelAsset('bard_field_with_html', $bardContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});
