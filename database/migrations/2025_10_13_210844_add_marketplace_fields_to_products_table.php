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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('approval_status')->default('pending')->after('seller_id');
            $table->decimal('commission_rate', 5, 2)->default(0)->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('commission_rate');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['rejection_reason', 'approved_at', 'commission_rate', 'approval_status']);
            $table->dropConstrainedForeignId('seller_id');
        });
    }
};
