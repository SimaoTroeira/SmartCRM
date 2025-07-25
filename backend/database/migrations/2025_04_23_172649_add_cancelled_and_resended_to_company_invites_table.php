<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_invites', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('resended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_invites', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'resended_at']);
        });
    }
};

