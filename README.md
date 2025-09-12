# Asset Atlas

Track where your Statamic assets are used to make moving, replacing and deleting assets quick and easy.

## Requirements

This package is based on using a database. It doesn't matter if you use the database for Statamic or not, but AssetAtlas tracks all asset references in entries, terms, global-sets and users as database records. This can be a simple SQLite database as it is preset in current Laravel installations.

## How To Use

Install the package:

```
composer require visuellverstehen/statamic-asset-atlas
```

Publish and run the required migration:

```
php artisan vendor:publish --tag=asset_atlas_migrations
php artisan migrate
```

Now whenever you save an item that relates to asset, the reference is tracked in AssetAtlas. On moving, deleting or replacing an asset, AssetAtlas provides all references to the asset instead of the base Statamic logic of checking all possible items.

You can (and should) initialise the atlas using this command:

```
php please asset-atlas:scan
```

As records will be updated, you can use this command regularly to keep AssetAtlas up to date. Note however that this command currently doesn't remove unused references.

## More about us

- [www.visuellverstehen.de](https://visuellverstehen.de)

## License
The MIT license (MIT). Please take a look at the [license file](LICENSE.md) for more information.

