<?php

use Illuminate\Support\Facades\DB;
use Statamic\Facades\Entry;

beforeEach(function () {
    // Load structural fixtures (collections, blueprints, asset containers)
    $this->loadFixtures();
});

// Helper Functions

/**
 * Create an entry with top-level asset data
 */
function createEntryWithTopLevelAsset($fieldName, $fieldData) {
    $data = [
        'title' => 'Test Page with Asset',
        $fieldName => $fieldData,
    ];

    return Entry::make()
        ->collection('pages')
        ->blueprint('page')
        ->slug('test-page-' . $fieldName . '-' . time())
        ->data($data);
}

/**
 * Create an entry with nested asset data in replicator
 */
function createEntryWithNestedAsset($fieldName, $fieldData) {
    $replicatorData = [
        [
            'id' => uniqid(),
            $fieldName => $fieldData,
            'type' => 'new_set',
            'enabled' => true,
        ]
    ];

    return Entry::make()
        ->collection('pages')
        ->blueprint('page')
        ->slug('test-page-replicator-' . time())
        ->data([
            'title' => 'Test Page with Nested Asset',
            'replicator_field' => $replicatorData,
        ]);
}


// Tests for Top-Level Asset Fields

it('tracks top-level assets field references', function () {
    $asset = createTestAsset('test-assets-field.jpg');
    $asset->save();

    $entry = createEntryWithTopLevelAsset('assets_field', [$asset->path()]);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});

it('tracks top-level bard field asset references', function () {
    $asset = createTestAsset('test-bard-field.jpg');
    $asset->save();

    $bardContent = [
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Here is an image: ']
            ]
        ],
        [
            'type' => 'image',
            'attrs' => [
                'src' => 'asset::assets::' . $asset->path(),
                'alt' => 'Test image'
            ]
        ]
    ];

    $entry = createEntryWithTopLevelAsset('bard_field', $bardContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});

it('tracks top-level bard field with HTML asset references', function () {
    $asset = createTestAsset('test-bard-html-field.jpg');
    $asset->save();

    $bardContent = [
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Here is an image: ']
            ]
        ],
        [
            'type' => 'image',
            'attrs' => [
                'src' => 'asset::assets::' . $asset->path(),
                'alt' => 'Test image with HTML'
            ]
        ]
    ];

    $entry = createEntryWithTopLevelAsset('bard_field_with_html', $bardContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});

it('tracks top-level link field asset references', function () {
    $asset = createTestAsset('test-link-field.jpg');
    $asset->save();

    $linkData = 'asset::assets::' . $asset->path();

    $entry = createEntryWithTopLevelAsset('link_field', $linkData);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});

it('tracks top-level markdown field asset references', function () {
    $asset = createTestAsset('test-markdown-field.jpg');
    $asset->save();

    $markdownContent = "Here is some text with an image:\n\n![](statamic://asset::assets::" . $asset->path() . ")\n\nAnd some more text.";

    $entry = createEntryWithTopLevelAsset('content', $markdownContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});

// Tests for Nested Asset Fields (in Replicator)

it('tracks replicator nested assets field references', function () {
    $asset = createTestAsset('test-replicator-assets.jpg');
    $asset->save();

    $entry = createEntryWithNestedAsset('assets_field', [$asset->path()]);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});

it('tracks replicator nested bard field asset references', function () {
    $asset = createTestAsset('test-replicator-bard.jpg');
    $asset->save();

    $bardContent = [
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Text before image']
            ]
        ],
        [
            'type' => 'image',
            'attrs' => [
                'src' => 'asset::assets::' . $asset->path(),
                'alt' => null
            ]
        ]
    ];

    $entry = createEntryWithNestedAsset('bard_field', $bardContent);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});

it('tracks replicator nested link field asset references', function () {
    $asset = createTestAsset('test-replicator-link.jpg');
    $asset->save();

    $linkData = 'asset::assets::' . $asset->path();

    $entry = createEntryWithNestedAsset('link_field', $linkData);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset);
});

// Edge Cases and Multiple References

it('tracks multiple assets in single assets field', function () {
    $asset1 = createTestAsset('test-multiple-1.jpg');
    $asset2 = createTestAsset('test-multiple-2.jpg');
    $asset1->save();
    $asset2->save();

    $entry = createEntryWithTopLevelAsset('assets_field', [$asset1->path(), $asset2->path()]);
    $entry->save();

    expect($entry)->toBeTrackedFor($asset1, 1);
    expect($entry)->toBeTrackedFor($asset2, 1);

    // Verify total count for this entry
    $totalReferences = DB::table('asset_atlas')
        ->where('item_id', $entry->id())
        ->count();

    expect($totalReferences)->toBe(2);
});

it('tracks assets in mixed field types within same entry', function () {
    $assetForAssetsField = createTestAsset('test-mixed-assets.jpg');
    $assetForBardField = createTestAsset('test-mixed-bard.jpg');
    $assetForAssetsField->save();
    $assetForBardField->save();

    $bardContent = [
        [
            'type' => 'image',
            'attrs' => [
                'src' => 'asset::assets::' . $assetForBardField->path(),
                'alt' => 'Bard image'
            ]
        ]
    ];

    $entry = Entry::make()
        ->collection('pages')
        ->blueprint('page')
        ->slug('test-mixed-fields-' . time())
        ->data([
            'title' => 'Test Mixed Asset Fields',
            'assets_field' => [$assetForAssetsField->path()],
            'bard_field' => $bardContent,
        ]);

    $entry->save();

    expect($entry)->toBeTrackedFor($assetForAssetsField, 1);
    expect($entry)->toBeTrackedFor($assetForBardField, 1);

    // Verify total count for this entry
    $totalReferences = DB::table('asset_atlas')
        ->where('item_id', $entry->id())
        ->count();

    expect($totalReferences)->toBe(2);
});
