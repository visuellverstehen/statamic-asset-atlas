<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_atlas', function (Blueprint $table) {
            $table->id();
            $table->string('asset_path');
            $table->string('asset_container');
            $table->uuid('item_id');
            $table->string('item_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_atlas');
    }
};
