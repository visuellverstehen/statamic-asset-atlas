<?php

namespace VV\AssetAtlas;

use Illuminate\Support\Facades\DB;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Revisions\Revision;
use Statamic\Facades\Entry;
use Statamic\Revisions\WorkingCopy;

/**
 * Working copies (drafts of published entries) can reference assets their entry does not.
 * The atlas stores their references as `working_copy` rows with the entry id, so a move finds the entry.
 */
class WorkingCopies
{
    public static function of($item): ?WorkingCopy
    {
        if (! $item instanceof EntryContract || ! $item->revisionsEnabled()) {
            return null;
        }

        return $item->workingCopy();
    }

    public static function entry(Revision $revision): ?EntryContract
    {
        // Entry working copies use the key `collections/{collection}/{site}/{id}`.
        $parts = explode('/', $revision->key(), 4);

        return count($parts) === 4 && $parts[0] === 'collections'
            ? Entry::find($parts[3])
            : null;
    }

    public static function track(EntryContract $entry, WorkingCopy $workingCopy): void
    {
        DB::transaction(function () use ($entry, $workingCopy) {
            AssetAtlas::removeAllByItem($entry->id(), 'working_copy');
            AssetScanner::item($entry->makeFromRevision($workingCopy))->forWorkingCopy()->addReferences();
        });
    }

    public static function untrack(EntryContract $entry): void
    {
        AssetAtlas::removeAllByItem($entry->id(), 'working_copy');
    }
}
