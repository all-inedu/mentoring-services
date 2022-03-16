<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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
                'first_name'   => 'ALL-In',
                'last_name'    => 'Admin',
                'phone_number' => '8123123123',
                'email'        => 'admin@example.com',
                'password'     => Hash::make('12345678'),
                'status'       => true,
                'is_verified'  => true,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ),
            array(
                'first_name'   => 'ALL-In',
                'last_name'    => 'Mentor',
                'phone_number' => '8123123123',
                'email'        => 'mentor@example.com',
                'password'     => Hash::make('12345678'),
                'status'       => true,
                'is_verified'  => true,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ),
            array(
                'first_name'   => 'ALL-In',
                'last_name'    => 'Editor',
                'phone_number' => '8123123123',
                'email'        => 'editor@example.com',
                'password'     => Hash::make('12345678'),
                'status'       => true,
                'is_verified'  => true,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ),
            array(
                'first_name'   => 'ALL-In',
                'last_name'    => 'Alumni',
                'phone_number' => '8123123123',
                'email'        => 'alumni@example.com',
                'password'     => Hash::make('12345678'),
                'status'       => true,
                'is_verified'  => true,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ),
        );

        DB::table('users')->insert($data);
    }
}
