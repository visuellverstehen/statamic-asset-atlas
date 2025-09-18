<?php

namespace VV\AssetAtlas\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Listeners\Concerns\GetsItemsContainingData;
use VV\AssetAtlas\AssetScanner;

class Scan extends Command
{
    use GetsItemsContainingData, RunsInPlease;

    protected $signature = 'statamic:asset-atlas:scan';

    protected $description = 'Adds all asset references to AssetAtlas';

    public function handle()
    {
        // TODO: add `clear` param that clears the atlas first

        $this->getItemsContainingData()
            ->each(function ($item) {
                AssetScanner::item($item)
                    ->addReferences();
            });
    }

    protected function getTopLevelFields($item)
    {
        return $item->blueprint()->fields()->all();
    }
}
