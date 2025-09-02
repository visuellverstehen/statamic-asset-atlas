<?php

namespace VV\AssetAtlas;

use Illuminate\Database\Eloquent\Model;

class AssetReference extends Model
{
    protected $table = 'asset_atlas';
    
    protected $fillable = [
        'entry_id',
        'asset_path',
        'asset_container'
    ];
}
