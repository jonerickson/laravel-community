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
        Schema::table('warnings_consequences', function (Blueprint $table) {
            $table->integer('duration_days')->after('threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warnings_consequences', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
