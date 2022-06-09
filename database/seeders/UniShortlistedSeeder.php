<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniShortlistedSeeder extends Seeder
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
                'user_id' => 1,
                'student_id' => 1,
                'imported_id' => 'UNIV-001',
                'uni_name' => 'University Test',
                'uni_major' => 'Major Test',
                'status' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'user_id' => 1,
                'student_id' => 1,
                'imported_id' => 'UNIV-002',
                'uni_name' => 'University of Indonesia',
                'uni_major' => 'Data Scientist',
                'status' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'user_id' => 1,
                'student_id' => 1,
                'imported_id' => 'UNIV-003',
                'uni_name' => 'University of Communication',
                'uni_major' => 'Communication',
                'status' => '1',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'user_id' => 1,
                'student_id' => 1,
                'imported_id' => 'UNIV-004',
                'uni_name' => 'McDonald University',
                'uni_major' => 'Sales/Marketing',
                'status' => '1',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'user_id' => 1,
                'student_id' => 1,
                'imported_id' => 'UNIV-005',
                'uni_name' => 'Jakarta International University',
                'uni_major' => 'Digital Marketing',
                'status' => '2',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'user_id' => 1,
                'student_id' => 1,
                'imported_id' => 'UNIV-006',
                'uni_name' => 'Emirates of University',
                'uni_major' => 'Architecture',
                'status' => '3',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
        );

        DB::table('uni_shortlisteds')->insert($data);
    }
}
