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
        Schema::table('orders', function (Blueprint $table) {
            $table->bigInteger('amount_due')->nullable()->change();
            $table->bigInteger('amount_overpaid')->nullable()->change();
            $table->bigInteger('amount_paid')->nullable()->change();
            $table->bigInteger('amount_remaining')->nullable()->change();
        });

        Schema::table('orders_items', function (Blueprint $table) {
            $table->bigInteger('amount')->nullable()->change();
            $table->bigInteger('commission_amount')->nullable()->change();
            $table->bigInteger('quantity')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
