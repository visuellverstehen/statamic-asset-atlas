# Asset Atlas

Track where your Statamic assets are used to make moving, replacing and deleting assets quick and easy.

## Requirements

This package is based on using a database. It doesn't matter if you use the database for Statamic or not, but AssetAtlas tracks all asset references in entries, terms, global-sets and users as database records. This can be a simple SQLite database as it is preset in current Laravel installations.

## How to use

Install the package:

```bash
composer require visuellverstehen/statamic-asset-atlas
```

Publish and run the required migration:

```bash
php artisan vendor:publish --tag=asset_atlas_migrations
php artisan migrate
```

Now whenever you save an item that relates to an asset, the reference is tracked in AssetAtlas. On moving, deleting or replacing an asset, AssetAtlas provides all references to the asset instead of the base Statamic logic of checking all possible items.

You can (and should) initialise the atlas using this command:

```bash
php please asset-atlas:scan
```

As records will be updated, you can use this command regularly to keep AssetAtlas up to date. Note that this command currently doesn't remove unused references. However, you can clear the atlas before scanning by using the `reset` parameter:

```bash
php please asset-atlas:scan --reset

# Use (the) force if you don't want to bother with confirmation dialogues:
php please asset-atlas:scan --reset --force
```

## Support for Custom Fieldtypes

If you're developing a custom fieldtype that stores asset references, you can make it compatible with AssetAtlas by implementing the `ScansAssetReferences` interface.

### Prerequisites

Your fieldtype should already use Statamic's `UpdatesReferences` trait to participate in the reference update system. This enables Statamic to update references when assets are renamed or moved.

```php
use Statamic\Fieldtypes\UpdatesReferences;

class MyCustomFieldtype extends Fieldtype
{
    use UpdatesReferences;
    
    public function replaceAssetReferences($data, ?string $newValue, string $oldValue, string $container)
    {
        // Update asset references in your fieldtype's data format
        // Return the modified data
    }
}
```

### Adding Atlas Scanning Support

To enable AssetAtlas to discover and track asset references in your fieldtype, implement the `ScansAssetReferences` interface:

```php
use Statamic\Fieldtypes\UpdatesReferences;
use VV\AssetAtlas\Contracts\ScansAssetReferences;

class MyCustomFieldtype extends Fieldtype implements ScansAssetReferences
{
    use UpdatesReferences;
    
    /**
     * Implementation for Statamic's reference updates.
     */
    public function replaceAssetReferences($data, ?string $newValue, string $oldValue, string $container)
    {
        // Handle asset renames/moves
        // ...
    }
    
    /**
     * Implementation for AssetAtlas scanning.
     * 
     * @param mixed $data The field's stored data
     * @return array Array of ['container' => string, 'path' => string] references
     */
    public function scanAssetReferences($data): array
    {
        $container = $this->config('container');
        $references = [];
        
        // Extract asset references from your fieldtype's data format
        // Example: if your data is ['asset' => 'path/to/file.jpg']
        if (isset($data['asset'])) {
            $references[] = [
                'container' => $container,
                'path' => $data['asset'],
            ];
        }
        
        return $references;
    }
}
```

### Return Format

The `scanAssetReferences` method must return an array of associative arrays, each containing:

- `container`: The asset container handle (typically from `$this->config('container')`)
- `path`: The asset path relative to the container

### Example: Gallery Fieldtype

Here's a more complete example for a fieldtype that stores multiple assets with captions:

```php
use Statamic\Fieldtypes\UpdatesReferences;
use VV\AssetAtlas\Contracts\ScansAssetReferences;

class GalleryFieldtype extends Fieldtype implements ScansAssetReferences
{
    use UpdatesReferences;
    
    protected static $handle = 'gallery';
    
    public function replaceAssetReferences($data, ?string $newValue, string $oldValue, string $container)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        return collect($data)->map(function ($item) use ($newValue, $oldValue, $container) {
            if (($item['container'] ?? null) === $container && $item['path'] === $oldValue) {
                if ($newValue === null) {
                    return null; // Asset was deleted
                }
                $item['path'] = $newValue;
            }
            return $item;
        })->filter()->values()->all();
    }
    
    public function scanAssetReferences($data): array
    {
        if (!is_array($data)) {
            return [];
        }
        
        $container = $this->config('container');
        
        return collect($data)
            ->filter(fn ($item) => isset($item['path']))
            ->map(fn ($item) => [
                'container' => $item['container'] ?? $container,
                'path' => $item['path'],
            ])
            ->all();
    }
}
```

## More about us

At **visuellverstehen** we create innovative digital and design solutions with a special focus on the common good. With technical expertise, high creativity and strategic skills, we develop products with a personal character.

- [www.visuellverstehen.de](https://visuellverstehen.de)
- [www.visuellverstehen.de/en](https://visuellverstehen.de/en)

## License
The MIT license (MIT). Please take a look at the [license file](LICENSE.md) for more information.

