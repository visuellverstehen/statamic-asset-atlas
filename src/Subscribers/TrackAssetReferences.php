<?php

namespace VV\AssetAtlas\Subscribers;

use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\Subscriber;
use VV\AssetAtlas\AssetScanner;

class TrackAssetReferences extends Subscriber
{
    protected $listeners = [
        EntrySaved::class => 'handleEntrySaved',
        EntryDeleted::class => 'handleEntryDeleted',
    ];
    
    public function handleEntryDeleted(EntryDeleted $event)
    {
        AssetScanner::item($event->entry)
            ->removeReferences();
    }
    
    public function handleEntrySaved(EntrySaved $event)
    {
        $this->addReferences($event->entry);
    }
    
    protected function addReferences($item)
    {
        AssetScanner::item($item)
            ->checkOriginal()
            ->addReferences();
    }
}