<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
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
                "first_name" => 'Student',
                "last_name" => 'Dummy',
                "birthday" => '2010-03-02',
                "phone_number" => '0812323123',
                "grade" => '12',
                "email" => 'student.dummy@example.com',
                "email_verified_at" => date('Y-m-d H:i:s'),
                "address" => 'dummy address 123',
                "city" => 'DKI Jakarta',
                "total_exp" => 0,
                "image" => '',
                "provider" => null,
                "provider_id" => null,
                "password" => Hash::make('12345678'),
                "imported_from" => null,
                "imported_id" => null,
                "status" => 1,
                "is_verified" => 1,
                "remember_token" => null,
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
                "school_name" => 'Canada High School'
            ),
        );

        DB::table('students')->insert($data);
    }
}
