<?php

namespace Tests\Concerns;

use Statamic\Facades\Blueprint;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;

/**
 * Builds a saved item of each tracked type (entry, term, global) carrying an
 * asset in a top-level `assets_field`, so a single Pest dataset can exercise
 * every branch of the TrackAssetReferences subscriber and the scanner
 * across all item types — not just entries.
 */
trait CreatesTestItems
{
    /**
     * @param  'entry'|'term'|'global'  $type
     * @param  array<int, string>  $paths
     */
    protected function makeItemWithAsset(string $type, array $paths)
    {
        return match ($type) {
            'entry' => $this->createEntryWithTopLevelAsset('assets_field', $paths),
            'term' => $this->makeTermWithAsset($paths),
            'global' => $this->makeGlobalWithAsset($paths),
        };
    }

    protected function makeTermWithAsset(array $paths)
    {
        $this->ensureTaxonomy();

        $term = Term::make()
            ->slug('topic-'.uniqid())
            ->taxonomy('topics');

        $term->dataForLocale(Site::default()->handle(), [
            'title' => 'Test Topic',
            'assets_field' => $paths,
        ]);

        $term->save();

        return $term;
    }

    protected function makeGlobalWithAsset(array $paths)
    {
        $this->ensureGlobalSet();

        $variables = GlobalSet::find('settings')->in(Site::default()->handle());
        $variables->set('assets_field', $paths);
        $variables->save();

        return $variables;
    }

    protected function ensureTaxonomy(): void
    {
        if (Taxonomy::findByHandle('topics')) {
            return;
        }

        Blueprint::make('topic')
            ->setNamespace('taxonomies.topics')
            ->setContents($this->assetsFieldBlueprint())
            ->save();

        Taxonomy::make('topics')->title('Topics')->save();
    }

    protected function ensureGlobalSet(): void
    {
        if (GlobalSet::find('settings')) {
            return;
        }

        Blueprint::make('settings')
            ->setNamespace('globals')
            ->setContents($this->assetsFieldBlueprint())
            ->save();

        $set = GlobalSet::make('settings')->title('Settings');
        $set->addLocalization($set->makeLocalization(Site::default()->handle()));
        $set->save();
    }

    private function assetsFieldBlueprint(): array
    {
        return [
            'tabs' => [
                'main' => [
                    'sections' => [
                        [
                            'fields' => [
                                [
                                    'handle' => 'assets_field',
                                    'field' => ['type' => 'assets', 'container' => 'assets'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
