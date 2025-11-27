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
            $table->dropIndex('users_fingerprints_fingerprint_id_is_banned_index');
            $table->dropForeign('users_fingerprints_banned_by_foreign');
            $table->dropColumn(['is_banned', 'banned_at', 'ban_reason', 'banned_by']);
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
