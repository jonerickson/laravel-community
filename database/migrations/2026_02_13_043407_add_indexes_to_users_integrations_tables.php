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
        Schema::table('users_integrations', function (Blueprint $table) {
            $table->index(['provider', 'provider_id']);
            $table->index(['provider', 'provider_email']);
            $table->index(['provider', 'provider_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_integrations', function (Blueprint $table) {
            //
        });
    }
};
