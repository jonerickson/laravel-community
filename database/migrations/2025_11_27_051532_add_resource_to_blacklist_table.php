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
        Schema::table('blacklist', function (Blueprint $table) {
            $table->string('content')->nullable()->change();
            $table->after('warning_id', fn (Blueprint $table) => $table->nullableMorphs('resource'));
            $table->foreignId('created_by')->after('warning_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('whitelist', function (Blueprint $table) {
            $table->string('content')->nullable()->change();
            $table->after('filter', fn (Blueprint $table) => $table->nullableMorphs('resource'));
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
