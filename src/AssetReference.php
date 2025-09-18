<?php

namespace VV\AssetAtlas;

use Illuminate\Database\Eloquent\Model;

class AssetReference extends Model
{
    protected $table = 'asset_atlas';

    protected $fillable = [
        'asset_path',
        'asset_container',
        'item_id',
        'item_type',
    ];
}
