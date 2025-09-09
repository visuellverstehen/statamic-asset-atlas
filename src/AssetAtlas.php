<?php

namespace VV\AssetAtlas;

use Illuminate\Support\Facades\Facade;

class AssetAtlas extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Atlas::class;
    }
}