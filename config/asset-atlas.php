<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the table used to store asset references.
    |
    */

    'table' => 'asset_atlas',

    /*
    |--------------------------------------------------------------------------
    | Lazy Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When the number of asset references exceeds this threshold, queries will
    | return a lazy collection to avoid loading all records into memory at once.
    |
    */

    'lazy_threshold' => 500,

    /*
    |--------------------------------------------------------------------------
    | Item Types to Track
    |--------------------------------------------------------------------------
    |
    | The Statamic content types that will be scanned for asset references.
    | Available types: entry, term, global_var, user
    |
    */

    'item_types' => [
        'entry',
        'term',
        'global_var',
        'user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Types to Scan
    |--------------------------------------------------------------------------
    |
    | The field types that will be inspected for asset references during
    | scanning. Disabling a type here will skip those fields entirely.
    |
    */

    'field_types' => [
        'assets',
        'link',
        'bard',
        'markdown',
        'grid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Indices
    |--------------------------------------------------------------------------
    |
    | Whether to create additional database indices when running migrations.
    | Indices improve query performance but increase storage and write overhead.
    |
    */

    'database_indices' => true,

];
