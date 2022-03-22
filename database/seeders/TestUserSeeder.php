<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1 ; $i <= 40 ; $i++) {
            $data[] = array(
                'first_name'   => 'Mentor',
                'last_name'    => 'Dummy '.$i,
                'phone_number' => '8123123123',
                'email'        => 'mentor'.$i.'@example.com',
                'password'     => Hash::make('12345678'),
                'status'       => true,
                'is_verified'  => true,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            );
        }

        DB::table('users')->insert($data);
    }
}
