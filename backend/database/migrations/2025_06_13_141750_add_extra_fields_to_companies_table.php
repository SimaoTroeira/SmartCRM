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
            $table->string('company_type')->nullable();
            $table->string('website')->nullable();
            $table->string('email_contact')->nullable();
            $table->string('phone_contact')->nullable();
            $table->string('nif')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->integer('founded_year')->nullable();
            $table->integer('num_employees')->nullable();
            $table->string('revenue_range')->nullable();
            $table->text('notes')->nullable();
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'company_type',
                'website',
                'email_contact',
                'phone_contact',
                'nif',
                'country',
                'city',
                'founded_year',
                'num_employees',
                'revenue_range',
                'notes'
            ]);
        });
    }
};
