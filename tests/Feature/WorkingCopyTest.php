<?php

use Illuminate\Support\Facades\DB;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use VV\AssetAtlas\AssetScanner;
use VV\AssetAtlas\Subscribers\TrackAssetReferences;

beforeEach(function () {
    config([
        'statamic.revisions.enabled' => true,
        'statamic.revisions.path' => sys_get_temp_dir().'/asset-atlas-revisions-'.uniqid(),
    ]);
    Collection::find('pages')->revisionsEnabled(true)->save();
});

function saveWorkingCopy($entry, array $data)
{
    $workingCopy = $entry->makeWorkingCopy();
    $workingCopy->attribute('data', array_merge($workingCopy->attribute('data'), $data));
    $workingCopy->save();
}

function workingCopyRows($entry)
{
    return DB::table('asset_atlas')->where('item_id', $entry->id())->where('item_type', 'working_copy')->pluck('asset_path')->all();
}

it('tracks references that only a working copy has', function () {
    $asset = $this->createAsset('draft.jpg');
    $entry = $this->createEntryWithTopLevelAsset('assets_field', []);

    saveWorkingCopy($entry, ['assets_field' => [$asset->path()]]);
    expect(workingCopyRows($entry))->toBe(['draft.jpg']);

    Entry::find($entry->id())->deleteWorkingCopy();
    expect(workingCopyRows($entry))->toBe([]);
});

it('keeps working copy references when the entry is saved', function () {
    $asset = $this->createAsset('shared.jpg');
    $entry = $this->createEntryWithTopLevelAsset('assets_field', [$asset->path()]);
    saveWorkingCopy($entry, ['assets_field' => [$asset->path()]]);

    // The prune pass of an entry save, driven directly like the other pruning tests.
    $original = $entry->data()->all();
    $entry->set('assets_field', []);
    AssetScanner::item($entry)->setOriginal($original)->checkOriginal()->addReferences();

    expect(workingCopyRows($entry))->toBe(['shared.jpg']);
});

it('updates moved paths in the entry and its working copy', function () {
    $published = $this->createAsset('published.jpg');
    $draft = $this->createAsset('draft.jpg');
    $entry = $this->createEntryWithTopLevelAsset('assets_field', [$published->path()]);
    saveWorkingCopy($entry, ['assets_field' => [$published->path(), $draft->path()]]);

    $published->move('moved');
    $draft->move('moved');

    $entry = Entry::find($entry->id());
    expect($entry->get('assets_field'))->toBe(['moved/published.jpg'])
        ->and($entry->workingCopy()->attribute('data')['assets_field'])->toBe(['moved/published.jpg', 'moved/draft.jpg'])
        ->and(workingCopyRows($entry))->toEqualCanonicalizing(['moved/published.jpg', 'moved/draft.jpg']);
});

it('indexes working copies when scanning', function () {
    $asset = $this->createAsset('draft.jpg');
    $entry = $this->createEntryWithTopLevelAsset('assets_field', []);
    saveWorkingCopy($entry, ['assets_field' => [$asset->path()]]);
    DB::table('asset_atlas')->delete();

    $this->artisan('statamic:asset-atlas:scan')->assertSuccessful();

    expect(workingCopyRows($entry))->toBe(['draft.jpg']);
});

it('removes references of working copies that no longer exist when scanning', function () {
    $asset = $this->createAsset('draft.jpg');
    $entry = $this->createEntryWithTopLevelAsset('assets_field', []);
    saveWorkingCopy($entry, ['assets_field' => [$asset->path()]]);
    TrackAssetReferences::withoutListeners(fn () => Entry::find($entry->id())->deleteWorkingCopy());

    $this->artisan('statamic:asset-atlas:scan')->assertSuccessful();

    expect(workingCopyRows($entry))->toBe([]);
});
