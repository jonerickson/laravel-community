<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->morphs('followable');
            $table->timestamps();

            $table->unique(['created_by', 'followable_type', 'followable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
