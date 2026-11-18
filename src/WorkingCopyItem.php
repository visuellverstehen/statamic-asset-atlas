<?php

namespace VV\AssetAtlas;

use Statamic\Contracts\Entries\Entry;
use Statamic\Revisions\WorkingCopy;

/**
 * Lets Statamic's reference updater rewrite a working copy like a regular item.
 * Implements only what the updater calls: blueprint(), data() and save().
 * TODO: remove when moving to Statamic 6, which updates working copies itself.
 */
class WorkingCopyItem
{
    public function __construct(private Entry $entry, private WorkingCopy $workingCopy) {}

    public function blueprint()
    {
        return $this->entry->blueprint();
    }

    public function data($data = null)
    {
        if (func_num_args() === 0) {
            return collect($this->workingCopy->attribute('data') ?? []);
        }

        $this->workingCopy->attribute('data', $data);

        return $this;
    }

    public function save(): void
    {
        $this->workingCopy->save();
    }
}
