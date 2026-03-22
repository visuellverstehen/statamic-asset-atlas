<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('asset-atlas.table', 'asset_atlas'), function (Blueprint $table) {
            $table->id();
            $table->string('asset_path');
            $table->string('asset_container');
            $table->uuid('item_id');
            $table->string('item_type');
            $table->timestamps();

            $table->unique(['asset_path', 'asset_container', 'item_id']);

            if (config('asset-atlas.database_indices', true)) {
                $table->index('item_id');
                $table->index('item_type');
                $table->index(['item_id', 'item_type']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('asset-atlas.table', 'asset_atlas'));
    }
};
