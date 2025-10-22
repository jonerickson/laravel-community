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
        Schema::table('fingerprints', function (Blueprint $table) {
            $table->string('request_id')->nullable()->after('fingerprint_id');
            $table->dropColumn('fingerprint_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fingerprints', function (Blueprint $table) {
            //
        });
    }
};
