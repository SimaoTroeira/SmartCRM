<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        DB::table('roles')->insert([
            ['name' => 'Super Admin', 'code' => 'SA'],
            ['name' => 'Company Admin', 'code' => 'CA'],
            ['name' => 'Company User', 'code' => 'CU'],
        ]);
    }
}
