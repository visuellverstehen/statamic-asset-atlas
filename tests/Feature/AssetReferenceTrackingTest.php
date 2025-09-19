<?php

use Illuminate\Support\Facades\DB;
use Statamic\Facades\Entry;

beforeEach(function () {
    // Load structural fixtures (collections, blueprints, asset containers)
    $this->loadFixtures();
});

it('creates asset reference when entry with top-level asset field is saved', function () {
    // Create a test asset
    $asset = createTestAsset('test-image.jpg');
    $asset->save();

    // Create an entry with the asset in the top-level assets_field
    $entry = Entry::make()
        ->collection('pages')
        ->blueprint('page')
        ->slug('test-page')
        ->data([
            'title' => 'Test Page with Asset',
            'assets_field' => [$asset->path()], // Top-level asset reference
        ]);
    // Save the entry - this should trigger asset reference tracking
    $entry->save();

    // Check if a reference was created in the asset_atlas table
    $this->assertDatabaseHas('asset_atlas', [
        'asset_path' => 'test-image.jpg',
        'asset_container' => 'assets',
        'item_id' => $entry->id(),
        // Don't check item_type as we don't know the exact implementation yet
    ]);

    // Verify we have exactly one reference
    $referenceCount = DB::table('asset_atlas')
        ->where('asset_path', 'test-image.jpg')
        ->where('item_id', $entry->id())
        ->count();

    expect($referenceCount)->toBe(1);
});
