<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scout_indexes', function (Blueprint $table) {
            $table->id();
            $table->string('searchable_type');
            $table->string('searchable_id');
            $table->json('searchable_data');
            $table->timestamps();

            $table->index(['searchable_type', 'searchable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scout_indexes');
    }
};
