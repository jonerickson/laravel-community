<?php

declare(strict_types=1);

use App\Enums\FilterType;
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
        Schema::create('whitelist', function (Blueprint $table) {
            $table->id();
            $table->string('content');
            $table->text('description')->nullable();
            $table->string('filter')->default(FilterType::String);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whitelist');
    }
};
