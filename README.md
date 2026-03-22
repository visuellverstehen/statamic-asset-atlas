# Asset Atlas

Track where your Statamic assets are used to make moving, replacing and deleting assets quick and easy.

## Requirements

- **PHP**: 8.3+
- **Laravel**: 12+
- **Statamic**: 6.0+

This package requires a database. It doesn't matter if you use the database for Statamic or not, but AssetAtlas tracks all asset references in entries, terms, global-sets and users as database records. This can be a simple SQLite database (as it is preset in current Laravel installations), but works with any Laravel-supported database.

## Installation

Install the package:

```bash
composer require visuellverstehen/statamic-asset-atlas
```

Publish the config file (optional but recommended):

```bash
php artisan vendor:publish --tag=asset-atlas-config
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag=asset-atlas-migrations
php artisan migrate
```

## Basic Usage

Whenever you save an item that contains asset references, AssetAtlas automatically tracks them. When moving, deleting, or replacing an asset, AssetAtlas provides instant reference lookup instead of scanning all content.

Initialize or refresh the atlas:

```bash
php please asset-atlas:scan
```

Clear and rescan:

```bash
php please asset-atlas:scan --reset

# Use (the) force if you don't want to bother with confirmation dialogues:
php please asset-atlas:scan --reset --force
```

## Configuration

After publishing the config file (`config/asset-atlas.php`), you can customize:

```php
return [
    // Database table name
    'table' => 'asset_atlas',

    // Use lazy collections for large sites
    'lazy_collections' => false,

    // Item types to track: entry, term, global_var, user
    'item_types' => ['entry', 'term', 'global_var', 'user'],

    // Field types to scan for assets
    'field_types' => ['assets', 'link', 'bard', 'markdown', 'grid'],

    // Create database indices for better query performance
    'database_indices' => true,
];
```

### Lazy Collections

For sites with many asset references, enable `lazy_collections` to avoid loading all records into memory:

```php
'lazy_collections' => true,
```

This makes `find()`, `findAll()`, and similar methods return `LazyCollection` instead of `Collection`.

### Disabling Item Types

If you don't want to track certain content types:

```php
'item_types' => ['entry', 'global_var'], // Skip terms and users
```

## Upgrade Guide

### Upgrading from v0.x to v2.0

v2.0 is a major release with breaking changes. Follow these steps:

#### 1. Requirements

Ensure your environment meets the new requirements:
- PHP 8.3+
- Laravel 12+
- Statamic 6.0+

#### 2. Update the package

```bash
composer require visuellverstehen/statamic-asset-atlas:^2.0
```

#### 3. Publish config (new in v2.0)

```bash
php artisan vendor:publish --tag=asset-atlas-config
```

#### 4. Run migrations

The table structure is unchanged, but ensure migrations are up to date:

```bash
php artisan migrate
```

#### 5. Rescan (recommended)

Reset and rescan to ensure all references are tracked:

```bash
php please asset-atlas:scan --reset --force
```

#### Breaking Changes

| Change | Impact |
|--------|--------|
| `Atlas::find()` return type | Now returns `Collection\|LazyCollection` - code type-hinting `Collection` may need updates |
| Statamic v6 required | v5 and below are not supported |
| Laravel 12 required | Laravel 11 and below are not supported |

#### New Features to Consider

- **Grid field support**: Asset references in Grid fields are now tracked automatically
- **Custom fieldtypes**: Third-party fieldtypes can implement `ScansAssetReferences` for tracking
- **Lazy collections**: Enable in config for better memory usage on large sites

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
