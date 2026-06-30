<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Storage;
use Statamic\Assets\Asset;
use Statamic\Entries\Entry;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Entry as EntryFacade;

trait CreatesTestEntries
{
    /**
     * Create an entry with top-level asset data
     */
    protected function createEntryWithTopLevelAsset(string $fieldName, mixed $fieldData): Entry
    {
        $data = [
            'title' => 'Test Page with Asset',
            $fieldName => $fieldData,
        ];

        $entry = EntryFacade::make()
            ->collection('pages')
            ->blueprint('page')
            ->slug('test-page-'.$fieldName.'-'.time())
            ->data($data);

        $entry->save();

        return $entry;
    }

    /**
     * Create an entry with nested asset data in replicator
     */
    protected function createEntryWithNestedAsset(string $fieldName, mixed $fieldData): Entry
    {
        $replicatorData = [
            [
                'id' => uniqid(),
                $fieldName => $fieldData,
                'type' => 'new_set',
                'enabled' => true,
            ],
        ];

        $entry = EntryFacade::make()
            ->collection('pages')
            ->blueprint('page')
            ->slug('test-page-replicator-'.time())
            ->data([
                'title' => 'Test Page with Nested Asset',
                'replicator_field' => $replicatorData,
            ]);

        $entry->save();

        return $entry;
    }

    /**
     * Create an entry with asset data in a grid row
     */
    protected function createEntryWithGridAsset(string $fieldName, mixed $fieldData): Entry
    {
        $entry = EntryFacade::make()
            ->collection('pages')
            ->blueprint('page')
            ->slug('test-page-grid-'.time())
            ->data([
                'title' => 'Test Page with Grid Asset',
                'grid_field' => [
                    [
                        'id' => uniqid(),
                        $fieldName => $fieldData,
                    ],
                ],
            ]);

        $entry->save();

        return $entry;
    }

    /**
     * Create an entry with asset data inside a bard set node
     */
    protected function createEntryWithBardSetAsset(string $fieldName, mixed $fieldData): Entry
    {
        $entry = EntryFacade::make()
            ->collection('pages')
            ->blueprint('page')
            ->slug('test-page-bard-set-'.time())
            ->data([
                'title' => 'Test Page with Bard Set Asset',
                'bard_set_field' => [
                    [
                        'type' => 'set',
                        'attrs' => [
                            'id' => uniqid(),
                            'values' => [
                                'type' => 'media_set',
                                $fieldName => $fieldData,
                            ],
                        ],
                    ],
                ],
            ]);

        $entry->save();

        return $entry;
    }

    /**
     * Create an entry with asset data in multiple replicator sets.
     * Each element of \$setsData becomes one replicator set of type `new_set`,
     * keyed by an `id`, carrying the given field => data pairs.
     *
     * @param  array<int, array<string, mixed>>  $setsData
     */
    protected function createEntryWithReplicatorSets(array $setsData): Entry
    {
        $replicatorData = array_map(function (array $fieldData) {
            return [
                'id' => uniqid(),
                'type' => 'new_set',
                'enabled' => true,
            ] + $fieldData;
        }, $setsData);

        $entry = EntryFacade::make()
            ->collection('pages')
            ->blueprint('page')
            ->slug('test-page-replicator-multi-'.time().'-'.uniqid())
            ->data([
                'title' => 'Test Page with Multiple Replicator Sets',
                'replicator_field' => $replicatorData,
            ]);

        $entry->save();

        return $entry;
    }

    /**
     * Create an entry with asset data in multiple grid rows.
     *
     * @param  array<int, array<string, mixed>>  $rowsData
     */
    protected function createEntryWithGridRows(array $rowsData): Entry
    {
        $gridData = array_map(function (array $fieldData) {
            return ['id' => uniqid()] + $fieldData;
        }, $rowsData);

        $entry = EntryFacade::make()
            ->collection('pages')
            ->blueprint('page')
            ->slug('test-page-grid-multi-'.time().'-'.uniqid())
            ->data([
                'title' => 'Test Page with Multiple Grid Rows',
                'grid_field' => $gridData,
            ]);

        $entry->save();

        return $entry;
    }

    /**
     * Create an entry with asset data in multiple bard set nodes.
     *
     * @param  array<int, array<string, mixed>>  $nodesData
     */
    protected function createEntryWithBardSetNodes(array $nodesData): Entry
    {
        $bardData = array_map(function (array $fieldData) {
            return [
                'type' => 'set',
                'attrs' => [
                    'id' => uniqid(),
                    'values' => ['type' => 'media_set'] + $fieldData,
                ],
            ];
        }, $nodesData);

        $entry = EntryFacade::make()
            ->collection('pages')
            ->blueprint('page')
            ->slug('test-page-bard-set-multi-'.time().'-'.uniqid())
            ->data([
                'title' => 'Test Page with Multiple Bard Set Nodes',
                'bard_set_field' => $bardData,
            ]);

        $entry->save();

        return $entry;
    }

    /**
     * Create a test asset with specified filename and container
     */
    protected function createAsset(string $filename = 'test-image.jpg', string $container = 'assets'): Asset
    {
        $assetContainer = AssetContainer::findByHandle($container);
        Storage::disk('test_disk')->put($filename, 'fake image content');

        $asset = new Asset;
        $asset->container($assetContainer);
        $asset->path($filename);
        $asset->save();

        return $asset;
    }
}
