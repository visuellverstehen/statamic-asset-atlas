<?php

use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

it('updates asset references in working copy when asset is renamed', function () {
    config(['statamic.revisions.enabled' => true]);

    $collection = tap(Collection::make('wc_pages')->revisionsEnabled(true))->save();

    Blueprint::make('page')
        ->setNamespace('collections.wc_pages')
        ->setContents([
            'fields' => [
                ['handle' => 'hero', 'field' => ['type' => 'assets', 'container' => 'assets', 'max_files' => 1]],
            ],
        ])
        ->save();

    $asset = $this->createAsset('working-copy-hero.jpg');

    $entry = tap(Entry::make()
        ->collection($collection)
        ->slug('wc-test')
        ->data(['hero' => $asset->path()])
    )->save();

    expect($entry->get('hero'))->toBe('working-copy-hero.jpg');
    expect($entry->hasWorkingCopy())->toBeFalse();

    // Create a working copy
    $entry->makeWorkingCopy()->save();
    expect($entry->fresh()->hasWorkingCopy())->toBeTrue();
    expect($entry->fresh()->workingCopy()->attributes()['data']['hero'] ?? null)->toBe('working-copy-hero.jpg');

    // The Atlas should track the asset on the published entry
    expect($entry)->toBeTrackedFor($asset);

    // Rename the asset — triggers UpdateAssetReferences
    $asset->path('working-copy-hero-renamed.jpg')->save();

    // Published entry should be updated
    expect($entry->fresh()->get('hero'))->toBe('working-copy-hero-renamed.jpg');

    // Working copy should also be updated
    expect($entry->fresh()->workingCopy()->attributes()['data']['hero'] ?? null)->toBe('working-copy-hero-renamed.jpg');

    // Atlas should reflect the new path
    $renamedAsset = $this->createAsset('working-copy-hero-renamed.jpg');
    expect($entry->fresh())->toBeTrackedFor($renamedAsset);
});
