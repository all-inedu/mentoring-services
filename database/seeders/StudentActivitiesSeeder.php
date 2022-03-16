<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentActivitiesSeeder extends Seeder
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
                'prog_id' => 1,
                'student_id' => 1,
                'user_id' => null,
                'std_act_status' => "waiting",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'handled_by' => null 
            ),
        );

        DB::table('student_activities')->insert($data);
    }
}
