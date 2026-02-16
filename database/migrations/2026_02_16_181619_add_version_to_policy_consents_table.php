<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('policy_consents', function (Blueprint $table) {
            $table->string('version')->nullable()->after('context');
        });

        DB::table('policy_consents')
            ->join('policies', 'policy_consents.policy_id', '=', 'policies.id')
            ->whereNull('policy_consents.version')
            ->update(['policy_consents.version' => DB::raw('policies.version')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_consents', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
