<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                'first_name' => 'super',
                'last_name' => 'admin',
                'birthday' => null,
                'phone_number' => null,
                'role_id' => 5,
                'email' => 'admin@all-inedu.com', //TODO diganti sesuai real email admin 
                'email_verified_at' => Carbon::now(),
                'password' => 'admin123',
                'address' => null,
                'total_exp' => 0,
                'profile_picture' => null,
                'imported_from' => null,
                'ext_id' => null,
                'status' => true,
                'remember_token' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'is_verified' => 1
            )
        );

        DB::table('users')->insert($data);
    }
}
