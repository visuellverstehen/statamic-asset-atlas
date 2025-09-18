<?php

namespace VV\AssetAtlas\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Listeners\Concerns\GetsItemsContainingData;
use VV\AssetAtlas\AssetReference;
use VV\AssetAtlas\AssetScanner;

class Scan extends Command
{
    use GetsItemsContainingData, RunsInPlease;

    protected $signature = 'statamic:asset-atlas:scan
                            {--reset : Clear the entire atlas before scanning}
                            {--force : Skip confirmation when using --reset}';

    protected $description = 'Scan all content items and add asset references to the Asset Atlas';

    protected $help = 'This command scans all content items (entries, terms, globals, users) in your Statamic installation and indexes all asset references into the Asset Atlas database. Use the --reset option to clear the existing atlas before scanning. Add --force to skip the confirmation prompt.';

    public function handle()
    {
        if ($this->option('reset')) {
            $this->clearAtlas();
        }

        $this->info('Scanning for asset references...');

        $items = $this->getItemsContainingData();
        $totalItems = $items->count();

        if ($totalItems === 0) {
            $this->info('No content items found to scan.');

            return;
        }

        $this->info("Found {$totalItems} content items to process");

        $referenceCount = AssetReference::count();

        $this->info('Processing...');

        $processedCount = 0;
        $items->each(function ($item) use (&$processedCount) {
            AssetScanner::item($item)->addReferences();
            $processedCount++;
        });

        $newReferenceCount = AssetReference::count();
        $referencesAdded = $newReferenceCount - $referenceCount;

        $this->info('✓ Scan complete!');
        $this->info("  • Processed {$processedCount} items");
        $this->info("  • Asset references in atlas: {$newReferenceCount}");

        if ($referencesAdded > 0) {
            $this->info("  • New references added: {$referencesAdded}");
        } elseif ($referencesAdded < 0) {
            $removed = abs($referencesAdded);
            $this->info("  • References removed: {$removed}");
        }
    }

    protected function clearAtlas(): void
    {
        $count = AssetReference::count();

        if ($count === 0) {
            $this->info('Atlas is already empty.');

            return;
        }

        if ($this->option('force') || $this->confirm("This will delete {$count} existing references from the atlas. Continue?")) {
            AssetReference::truncate();
            $this->info("Atlas cleared successfully. Removed {$count} references.");
        } else {
            $this->info('Operation cancelled.');
            exit;
        }

        $this->newLine();
    }
}
