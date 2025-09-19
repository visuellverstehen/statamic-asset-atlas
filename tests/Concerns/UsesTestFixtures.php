<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Symfony\Component\Yaml\Yaml;

trait UsesTestFixtures
{
    protected function loadFixtures(): void
    {
        $this->loadCollections();
        $this->loadBlueprints();
        $this->loadAssetContainers();
    }

    protected function loadCollections(): void
    {
        foreach (File::files(__DIR__.'/../fixtures/collections') as $file) {
            $data = Yaml::parseFile($file->getPathname());
            $collection = Collection::make();

            $collection->handle($file->getFilenameWithoutExtension());
            $collection->title($data['title']);
            $collection->template($data['template']);
            $collection->layout($data['layout']);
            $collection->route($data['route']);
            $collection->sortDirection($data['sort_dir']);
            $collection->structureContents($data['structure']);
            $collection->save();
        }
    }

    protected function loadBlueprints(): void
    {
        foreach (File::directories(__DIR__.'/../fixtures/blueprints/collections') as $collectionDir) {
            $collectionHandle = basename($collectionDir);

            foreach (File::files($collectionDir) as $file) {
                $data = Yaml::parseFile($file->getPathname());
                $blueprintHandle = $file->getFilenameWithoutExtension();

                Blueprint::make($blueprintHandle)
                    ->setNamespace('collections.'.$collectionHandle)
                    ->setContents($data)
                    ->save();
            }
        }
    }

    protected function loadAssetContainers(): void
    {
        Storage::fake('test_disk');

        foreach (File::files(__DIR__.'/../fixtures/asset-containers') as $file) {
            $data = Yaml::parseFile($file->getPathname());
            $container = AssetContainer::make();

            $container->handle($file->getFilenameWithoutExtension());
            $container->title($data['title']);
            $container->disk($data['disk']);
            $container->allowUploads($data['allow_uploads']);
            $container->allowDownloading($data['allow_downloading']);
            $container->allowRenaming($data['allow_renaming']);
            $container->allowMoving($data['allow_moving']);
            $container->createFolders($data['create_folders']);
            $container->save();
        }
    }
}
