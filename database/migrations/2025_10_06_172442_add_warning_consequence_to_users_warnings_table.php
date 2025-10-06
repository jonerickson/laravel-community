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
        Schema::table('users_warnings', function (Blueprint $table) {
            $table->foreignId('warning_consequence_id')->nullable()->after('warning_id')->constrained('warnings_consequences')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_warnings', function (Blueprint $table) {
            $table->dropForeign(['warning_consequence_id']);
            $table->dropColumn('warning_consequence_id');
        });
    }
};
