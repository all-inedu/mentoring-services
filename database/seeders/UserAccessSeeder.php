<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Permission
class UserAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = array(
            array(
                'role_id' => 1,
                'per_scope_access' => '["admin"]',
                'per_desc' => 'can access admin menu',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ),
            array(
                'role_id' => 2,
                'per_scope_access' => '["mentor"]',
                'per_desc' => 'can access mentor menu',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ),
            array(
                'role_id' => 3,
                'per_scope_access' => '["editor"]',
                'per_desc' => 'can access editor menu',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ),
            array(
                'role_id' => 4,
                'per_scope_access' => '["alumni"]',
                'per_desc' => 'can access admin menu',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ),

        );

        DB::table('permissions')->insert($data);
    }
}
