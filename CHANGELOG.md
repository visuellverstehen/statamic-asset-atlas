# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v2.0.0 - tbd

### Breaking Changes

- **Statamic v6 required** - Minimum Statamic version is now 6.0
- **Laravel 12 required** - Minimum Laravel version is now 12
- **PHP 8.3 required** - Minimum PHP version is now 8.3
- **New config file** - Configuration now published via `asset-atlas.php` (run `php artisan vendor:publish --tag=asset-atlas-config`)
- **Database migration required** - Run `php artisan migrate` after updating

### New Features

- **Grid field support** - Asset references in Grid fields are now tracked (Phase 2.4)
- **Custom fieldtype support** - Third-party fieldtypes can implement `ScansAssetReferences` interface to participate in scanning (Phase 4.3)
- **Configuration file** - New config file for customizing behavior:
  - `lazy` - Enable lazy collections for large sites
  - `item_types` - Disable specific item types (entries, terms, globals, users)
  - `table` - Custom table name
  - `field_types` - Disable specific field type scanning

### Improvements

- **Transaction safety** - Database operations now wrapped in transactions for data integrity (Phase 2.3)
- **Performance optimizations** - Bulk operations and reduced query overhead (Phase 2.1, 2.2)
- **v6 event compatibility** - Updated event subscribers for Statamic v6's `_Saving`/`_Saved` event pattern (Phase 4.2)

### API Changes

- `Atlas::find()` returns `Collection|LazyCollection` based on config
- `Atlas::findAll()`, `findEntries()`, `findTerms()`, `findGlobalVar()`, `findUsers()` all support lazy mode
- New `Atlas::removeAllByItem()` method for bulk removal by item ID

### Test Coverage

- 39 tests covering all field types, custom fieldtypes, transactions, and API methods
- 152 assertions ensuring reliability

## v0.3.0 - 2025-09-23

### What's Changed

* Fixed an issue with removing unused references when saving an item
* Added tests by @el-schneider in https://github.com/visuellverstehen/statamic-asset-atlas/pull/3

**Full Changelog**: https://github.com/visuellverstehen/statamic-asset-atlas/compare/v0.2.0...v0.3.0

## v0.2.0 - 2025-09-19

### What's new?

* Improve scan command with better feedback and `--reset` and `--force` params by @el-schneider in https://github.com/visuellverstehen/statamic-asset-atlas/pull/1
* Add pint and some workflows by @el-schneider in https://github.com/visuellverstehen/statamic-asset-atlas/pull/2

### New Contributors

* @el-schneider made their first contribution in https://github.com/visuellverstehen/statamic-asset-atlas/pull/1

**Full Changelog**: https://github.com/visuellverstehen/statamic-asset-atlas/compare/v0.1.0...v0.2.0

## v0.1.0 - 2025-09-15

### What's new?

Everything!

This add-on for [Statamic](https://statamic.com) tracks where your assets are used to make moving, replacing and deleting assets quick and easy. Consider this an early initial release, where things might still change quickly as more real life test results come in.
