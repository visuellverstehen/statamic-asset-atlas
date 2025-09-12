<?php

namespace VV\AssetAtlas\Subscribers;

use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\GlobalVariablesDeleted;
use Statamic\Events\GlobalVariablesSaved;
use Statamic\Events\GlobalVariablesSaving;
use Statamic\Events\Subscriber;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Events\UserDeleted;
use Statamic\Events\UserSaved;
use Statamic\Facades\Blink;
use Statamic\Facades\GlobalVariables;
use VV\AssetAtlas\AssetScanner;

class TrackAssetReferences extends Subscriber
{
    protected $listeners = [
        EntryDeleted::class => 'handleEntryDeleted',
        EntrySaved::class => 'handleEntrySaved',
        GlobalVariablesDeleted::class => 'handleGlobalVarsDeleted',
        GlobalVariablesSaved::class => 'handleGlobalVarsSaved',
        GlobalVariablesSaving::class => 'handleGlobalVarsSaving',
        TermDeleted::class => 'handleTermDeleted',
        TermSaved::class => 'handleTermSaved',
        UserDeleted::class => 'handleUserDeleted',
        UserSaved::class => 'handleUserSaved',
    ];
    
    public function handleEntryDeleted(EntryDeleted $event)
    {
        $this->removeReferences($event->entry);
    }
    
    public function handleEntrySaved(EntrySaved $event)
    {
        $this->addReferences($event->entry);
    }
    
    public function handleGlobalVarsDeleted(GlobalVariablesDeleted $event)
    {
        $this->removeReferences($event->variables);
    }
    
    public function handleGlobalVarsSaved(GlobalVariablesSaved $event)
    {
        // Unfortunately GlobalVars don't track an original value yet,
        // so we can't clean up references in the same way.
        // To still be able to remove old references, we're temporarily
        // storing the og value in the respective -Saving event, so we 
        // can tell the scanner to use it for tidying up.
        
        $scanner = AssetScanner::item($event->variables);
        
        if ($original = Blink::get('assetatlas-globalvar-' . $event->variables->id())) {
            $scanner
                ->setOriginal($original)
                ->checkOriginal();
        }
        
        $scanner->addReferences();
    }
    
    public function handleGlobalVarsSaving(GlobalVariablesSaving $event)
    {
        $id = $event->variables->id();
        
        Blink::put(
            'assetatlas-globalvar-' . $id, 
            GlobalVariables::find($id)->data()->all()
        );
    }
    
    public function handleTermDeleted(TermDeleted $event)
    {
        $this->removeReferences($event->term);
    }
    
    public function handleTermSaved(TermSaved $event)
    {
        $this->addReferences($event->term);
    }
    
    public function handleUserDeleted(UserDeleted $event)
    {
        $this->removeReferences($event->user);
    }
    
    public function handleUserSaved(UserSaved $event)
    {
        $this->addReferences($event->user);
    }
    
    protected function addReferences($item)
    {
        AssetScanner::item($item)
            ->checkOriginal()
            ->addReferences();
    }
    
    protected function removeReferences($item)
    {
        AssetScanner::item($item)
            ->removeReferences();
    }
}