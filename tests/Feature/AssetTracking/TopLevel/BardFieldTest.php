<?php

it('tracks top-level bard field asset references', function () {
    $asset = $this->createAsset('test-bard-field.jpg');
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

    $asset2 = $this->createAsset('test-bard-field-2.jpg');
    $asset2->save();

    $updatedBardContent = $bardContent;
    $updatedBardContent[] = [
        'type' => 'image',
        'attrs' => [
            'src' => 'asset::assets::'.$asset2->path(),
            'alt' => 'Second test image',
        ],
    ];

    $entry->set('bard_field', $updatedBardContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $asset->delete();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $entry->delete();

    expect($entry)->not->toBeTrackedFor($asset2);
});

it('tracks top-level bard field with HTML asset references', function () {
    $asset = $this->createAsset('test-bard-html-field.jpg');
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

    $asset2 = $this->createAsset('test-bard-html-field-2.jpg');
    $asset2->save();

    $updatedBardContent = $bardContent;
    $updatedBardContent[] = [
        'type' => 'image',
        'attrs' => [
            'src' => 'asset::assets::'.$asset2->path(),
            'alt' => 'Second test image with HTML',
        ],
    ];

    $entry->set('bard_field_with_html', $updatedBardContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $asset->delete();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    $entry->delete();

    expect($entry)->not->toBeTrackedFor($asset2);
});
