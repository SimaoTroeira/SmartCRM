<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('user_company_roles', function (Blueprint $table) {
            $table->softDeletes(); // cria a coluna deleted_at
        });
    }

    public function down()
    {
        Schema::table('user_company_roles', function (Blueprint $table) {
            $table->dropSoftDeletes(); // remove a coluna deleted_at
        });
    }
};
