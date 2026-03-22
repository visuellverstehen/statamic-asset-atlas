<?php

namespace Tests\Support;

use Statamic\Fields\Fieldtype;
use Statamic\Fieldtypes\UpdatesReferences;
use VV\AssetAtlas\Contracts\ScansAssetReferences;

/**
 * Test fieldtype for verifying custom fieldtype scanning support.
 *
 * This simulates a third-party fieldtype that stores asset references
 * in a custom data format. It uses the UpdatesReferences trait to
 * participate in Statamic's reference update system, and implements
 * ScansAssetReferences to enable Atlas scanning.
 *
 * Data format:
 * - Single asset: ['asset_path' => 'path/to/file.jpg']
 * - Multiple assets: ['assets' => ['path/to/file1.jpg', 'path/to/file2.jpg']]
 */
class TestCustomAssetsFieldtype extends Fieldtype implements ScansAssetReferences
{
    use UpdatesReferences;

    protected static $handle = 'test_custom_assets';

    /**
     * Handle asset renames/moves for Statamic's reference update system.
     */
    public function replaceAssetReferences($data, ?string $newValue, string $oldValue, string $container)
    {
        if (isset($data['asset_path']) && $data['asset_path'] === $oldValue && $this->config('container') === $container) {
            $data['asset_path'] = $newValue;
        }

        if (isset($data['assets']) && is_array($data['assets'])) {
            $data['assets'] = collect($data['assets'])
                ->map(fn ($path) => $path === $oldValue ? $newValue : $path)
                ->filter()
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * Extract asset references for Atlas scanning.
     */
    public function scanAssetReferences($data): array
    {
        $container = $this->config('container');
        $references = [];

        if (isset($data['asset_path'])) {
            $references[] = ['container' => $container, 'path' => $data['asset_path']];
        }

        if (isset($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $path) {
                $references[] = ['container' => $container, 'path' => $path];
            }
        }

        return $references;
    }
}
