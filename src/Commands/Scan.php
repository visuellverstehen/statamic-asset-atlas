<?php 

namespace VV\AssetAtlas\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Listeners\Concerns\GetsItemsContainingData;
use VV\AssetAtlas\AssetScanner;

class Scan extends Command
{
    use RunsInPlease, GetsItemsContainingData;
    
    protected $signature = 'statamic:asset-atlas:scan';
    protected $description = 'Adds all asset references to AssetAtlas';
    
    public function handle()
    {
        $this->getItemsContainingData()
            ->each(function ($item) {
                AssetScanner::item($item)
                    ->scanForReferences();
            });
    }
    
    protected function getTopLevelFields($item)
    {
        return $item->blueprint()->fields()->all();
    }
}