<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warnings_consequences', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedInteger('threshold');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warnings_consequences');
    }
};
