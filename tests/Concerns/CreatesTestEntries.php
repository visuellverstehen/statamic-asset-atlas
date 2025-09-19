<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Storage;
use Statamic\Assets\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Entry;

trait CreatesTestEntries
{
    /**
     * Create an entry with top-level asset data
     */
    protected function createEntryWithTopLevelAsset($fieldName, $fieldData)
    {
        $data = [
            'title' => 'Test Page with Asset',
            $fieldName => $fieldData,
        ];

        return Entry::make()
            ->collection('pages')
            ->blueprint('page')
            ->slug('test-page-'.$fieldName.'-'.time())
            ->data($data);
    }

    /**
     * Create an entry with nested asset data in replicator
     */
    protected function createEntryWithNestedAsset($fieldName, $fieldData)
    {
        $replicatorData = [
            [
                'id' => uniqid(),
                $fieldName => $fieldData,
                'type' => 'new_set',
                'enabled' => true,
            ],
        ];

        return Entry::make()
            ->collection('pages')
            ->blueprint('page')
            ->slug('test-page-replicator-'.time())
            ->data([
                'title' => 'Test Page with Nested Asset',
                'replicator_field' => $replicatorData,
            ]);
    }

    /**
     * Create a test asset with specified filename and container
     */
    protected function createTestAsset(string $filename = 'test-image.jpg', string $container = 'assets'): Asset
    {
        $assetContainer = AssetContainer::findByHandle($container);
        Storage::disk('test_disk')->put($filename, 'fake image content');

        $asset = new Asset;
        $asset->container($assetContainer);
        $asset->path($filename);

        return $asset;
    }
}
