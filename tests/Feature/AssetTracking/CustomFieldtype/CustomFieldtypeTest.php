<?php

namespace Tests\Feature\AssetTracking\CustomFieldtype;

use Statamic\Facades\Blueprint;
use Statamic\Facades\Blink;
use Tests\Support\TestCustomAssetsFieldtype;
use Tests\TestCase;

beforeEach(function () {
    Blink::flush();
    app('statamic.fieldtypes')->put('test_custom_assets', TestCustomAssetsFieldtype::class);
});

it('tracks asset references from custom fieldtypes implementing ScansAssetReferences', function () {
    $asset = $this->createAsset('custom-field-asset.jpg');

    Blueprint::make('custom_test')
        ->setNamespace('collections.pages')
        ->setContents([
            'title' => 'Custom Test',
            'fields' => [
                ['handle' => 'title', 'field' => ['type' => 'text', 'required' => true]],
                ['handle' => 'custom_assets_field', 'field' => [
                    'type' => 'test_custom_assets',
                    'container' => 'assets',
                    'display' => 'Custom Assets Field',
                ]],
            ],
        ])
        ->save();

    $entry = $this->createEntryWithCustomField('custom_assets_field', [
        'asset_path' => $asset->path(),
    ], 'custom_test');

    expect($entry)->toBeTrackedFor($asset);

    // Test update
    $entry = clone $entry;
    $asset2 = $this->createAsset('custom-field-asset-2.jpg');

    $entry->set('custom_assets_field', ['asset_path' => $asset2->path()]);
    $entry->save();

    expect($entry)->not->toBeTrackedFor($asset);
    expect($entry)->toBeTrackedFor($asset2);

    // Test deletion
    $entry->delete();
    expect($entry)->not->toBeTrackedFor($asset2);
});

it('tracks multiple assets from custom fieldtype', function () {
    $asset1 = $this->createAsset('multi-custom-1.jpg');
    $asset2 = $this->createAsset('multi-custom-2.jpg');

    Blueprint::make('custom_multi_test')
        ->setNamespace('collections.pages')
        ->setContents([
            'title' => 'Custom Multi Test',
            'fields' => [
                ['handle' => 'title', 'field' => ['type' => 'text', 'required' => true]],
                ['handle' => 'custom_assets_field', 'field' => [
                    'type' => 'test_custom_assets',
                    'container' => 'assets',
                    'display' => 'Custom Assets Field',
                ]],
            ],
        ])
        ->save();

    $entry = $this->createEntryWithCustomField('custom_assets_field', [
        'assets' => [$asset1->path(), $asset2->path()],
    ], 'custom_multi_test');

    expect($entry)->toBeTrackedFor($asset1);
    expect($entry)->toBeTrackedFor($asset2);
});