<?php

use Illuminate\Support\LazyCollection;
use Statamic\Facades\Entry;
use Statamic\Listeners\UpdateAssetReferences;
use VV\AssetAtlas\AssetAtlas;
use VV\AssetAtlas\Subscribers\TrackAssetReferences;

it('updates indexed items and items supplied by the Statamic additional hook', function (string $operation, ?string $newPath) {
    $asset = $this->createAsset('original.jpg');
    $indexed = $this->createEntryWithTopLevelAsset('assets_field', [$asset->path()]);
    $additional = Entry::make()->collection('pages')->blueprint('page')
        ->slug('additional')->data(['assets_field' => [$asset->path()]]);
    TrackAssetReferences::withoutListeners(fn () => $additional->save());

    expect(AssetAtlas::findEntries($asset->path(), 'assets')->map->id()->all())
        ->toBe([$indexed->id()]);

    UpdateAssetReferences::hook('additional', function ($payload, $next) use ($additional) {
        return $next(LazyCollection::make(function () use ($additional) {
            yield $additional;
        }));
    });

    match ($operation) {
        'move' => $asset->move('moved'),
        'rename' => $asset->rename('renamed'),
        'delete' => $asset->delete(),
    };

    foreach ([$indexed, $additional] as $entry) {
        expect(Entry::find($entry->id())->get('assets_field'))
            ->toBe($newPath === null ? null : [$newPath]);
    }
})->with([
    'move' => ['move', 'moved/original.jpg'],
    'rename' => ['rename', 'renamed.jpg'],
    'delete' => ['delete', null],
]);
