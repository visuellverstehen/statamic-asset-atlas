<?php

namespace VV\AssetAtlas\Subscribers;

use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\GlobalVariablesDeleted;
use Statamic\Events\GlobalVariablesSaved;
use Statamic\Events\GlobalVariablesSaving;
use Statamic\Events\Subscriber;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Events\TermSaving;
use Statamic\Events\UserDeleted;
use Statamic\Events\UserSaved;
use Statamic\Events\UserSaving;
use Statamic\Facades\Blink;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalVariables;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use VV\AssetAtlas\AssetScanner;

class TrackAssetReferences extends Subscriber
{
    protected $listeners = [
        EntryDeleted::class => 'handleEntryDeleted',
        EntrySaved::class => 'handleEntrySaved',
        EntrySaving::class => 'handleEntrySaving',
        GlobalVariablesDeleted::class => 'handleGlobalVarsDeleted',
        GlobalVariablesSaved::class => 'handleGlobalVarsSaved',
        GlobalVariablesSaving::class => 'handleGlobalVarsSaving',
        TermDeleted::class => 'handleTermDeleted',
        TermSaved::class => 'handleTermSaved',
        TermSaving::class => 'handleTermSaving',
        UserDeleted::class => 'handleUserDeleted',
        UserSaved::class => 'handleUserSaved',
        UserSaving::class => 'handleUserSaving',
    ];

    public function handleEntryDeleted(EntryDeleted $event)
    {
        $this->removeReferences($event->entry);
    }

    public function handleEntrySaving(EntrySaving $event): void
    {
        // Capture the stored (pre-save) data before the Stache writes the new version.
        // In v6, the Stache calls syncOriginal() on cached items during the save process,
        // which means getOriginal() is unreliable by the time EntrySaved fires.
        $this->captureOriginal('assetatlas-entry-', $event->entry->id(), fn ($id) => Entry::find($id));
    }

    public function handleEntrySaved(EntrySaved $event)
    {
        $this->addReferencesWithCapturedOriginal('assetatlas-entry-', $event->entry);
    }

    public function handleGlobalVarsDeleted(GlobalVariablesDeleted $event)
    {
        $this->removeReferences($event->variables);
    }

    public function handleGlobalVarsSaved(GlobalVariablesSaved $event)
    {
        $scanner = AssetScanner::item($event->variables);

        if ($original = Blink::get('assetatlas-globalvar-'.$event->variables->id())) {
            $scanner->setOriginal($original)->checkOriginal();
        }

        $scanner->addReferences();
    }

    public function handleGlobalVarsSaving(GlobalVariablesSaving $event)
    {
        $id = $event->variables->id();

        Blink::put(
            'assetatlas-globalvar-'.$id,
            GlobalVariables::find($id)->data()->all()
        );
    }

    public function handleTermDeleted(TermDeleted $event)
    {
        $this->removeReferences($event->term);
    }

    public function handleTermSaving(TermSaving $event): void
    {
        $this->captureOriginal('assetatlas-term-', $event->term->id(), fn ($id) => Term::find($id));
    }

    public function handleTermSaved(TermSaved $event)
    {
        $this->addReferencesWithCapturedOriginal('assetatlas-term-', $event->term);
    }

    public function handleUserDeleted(UserDeleted $event)
    {
        $this->removeReferences($event->user);
    }

    public function handleUserSaving(UserSaving $event): void
    {
        $this->captureOriginal('assetatlas-user-', $event->user->id(), fn ($id) => User::find($id));
    }

    public function handleUserSaved(UserSaved $event)
    {
        $this->addReferencesWithCapturedOriginal('assetatlas-user-', $event->user);
    }

    protected function captureOriginal(string $prefix, ?string $id, callable $finder): void
    {
        if (! $id) {
            return;
        }

        $existing = $finder($id);

        if (! $existing) {
            return;
        }

        Blink::put($prefix.$id, $existing->data()->all());
    }

    protected function addReferencesWithCapturedOriginal(string $prefix, $item): void
    {
        $scanner = AssetScanner::item($item);

        if ($original = Blink::get($prefix.$item->id())) {
            $scanner->setOriginal($original)->checkOriginal();
            Blink::forget($prefix.$item->id());
        }

        $scanner->addReferences();
    }

    protected function removeReferences($item)
    {
        AssetScanner::item($item)
            ->removeReferences();
    }
}
