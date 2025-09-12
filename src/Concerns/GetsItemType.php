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
        switch (true) {
            case $item instanceof Entry: return 'entry';
            case $item instanceof Term: return 'term';
            case $item instanceof Variables: return 'global_var';
            case $item instanceof User: return 'user';
        }
        
        return null;
    }
}