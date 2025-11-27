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
        Schema::table('blacklist', function (Blueprint $table) {
            $table->string('filter')->default(FilterType::String)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blacklist', function (Blueprint $table) {
            //
        });
    }
};
