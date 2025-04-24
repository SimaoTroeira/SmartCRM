<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('draft');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('draft')->default(0);
        });
    }
};
