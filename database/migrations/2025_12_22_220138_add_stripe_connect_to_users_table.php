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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('payouts_enabled')->default(false)->after('stripe_id');
            $table->string('external_payout_account_id')->nullable()->after('payouts_enabled');
            $table->timestamp('external_payout_account_onboarded_at')->nullable()->after('external_payout_account_id');
            $table->json('external_payout_account_capabilities')->nullable()->after('external_payout_account_onboarded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'payouts_enabled',
                'external_payout_account_id',
                'external_payout_account_onboarded_at',
                'external_payout_account_capabilities',
            ]);
        });
    }
};
