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
            if (Schema::hasColumn('users_warnings', 'expires_at')) {
                $table->renameColumn('expires_at', 'points_expire_at');
            }
        });

        Schema::table('users_warnings', function (Blueprint $table) {
            if (! Schema::hasColumn('users_warnings', 'consequence_expires_at')) {
                $table->timestamp('consequence_expires_at')->nullable()->after('points_expire_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_warnings', function (Blueprint $table) {
            $table->dropColumn('consequence_expires_at');
            $table->renameColumn('points_expire_at', 'expires_at');
        });
    }
};
