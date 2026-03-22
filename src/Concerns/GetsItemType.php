<?php

namespace VV\AssetAtlas\Concerns;

use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Globals\Variables;
use Statamic\Contracts\Taxonomies\Term;

trait GetsItemType
{
    public function getItemType($item): ?string
    {
        return match (true) {
            $item instanceof Entry => 'entry',
            $item instanceof Term => 'term',
            $item instanceof Variables => 'global_var',
            $item instanceof User => 'user',
            default => null,
        };
    }
}
