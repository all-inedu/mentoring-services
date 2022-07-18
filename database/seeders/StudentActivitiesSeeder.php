<?php

namespace Database\Seeders;

use App\Models\Students;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentActivitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //seeder for mentor activities
        $data = array(
            array(
                'prog_id' => 1,
                'student_id' => Students::inRandomOrder()->first()->id,
                'user_id' => 2,
                'std_act_status' => "waiting",
                'mt_confirm_status' => "confirmed",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'handled_by' => NULL,
                'location_link' => 'http://example-meeting.com/'.Str::random(10),
                'location_pw' => Str::random(10),
                'prog_dtl_id' => NULL,
                'call_with' => 'mentor',
                'module' => 'life skills',
                'call_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'call_status' => 'waiting'
            ),
            array(
                'prog_id' => 1,
                'student_id' => Students::inRandomOrder()->first()->id,
                'user_id' => 2,
                'std_act_status' => "waiting",
                'mt_confirm_status' => "confirmed",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'handled_by' => NULL,
                'location_link' => 'http://example-meeting.com/'.Str::random(10),
                'location_pw' => Str::random(10),
                'prog_dtl_id' => NULL,
                'call_with' => 'mentor',
                'module' => 'life skills',
                'call_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'call_status' => 'waiting'
            ),
            array(
                'prog_id' => 1,
                'student_id' => Students::inRandomOrder()->first()->id,
                'user_id' => 2,
                'std_act_status' => "confirmed",
                'mt_confirm_status' => "confirmed",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'handled_by' => NULL,
                'location_link' => 'http://example-meeting.com/'.Str::random(10),
                'location_pw' => Str::random(10),
                'prog_dtl_id' => NULL,
                'call_with' => 'mentor',
                'module' => 'life skills',
                'call_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'call_status' => 'waiting'
            ),
            array(
                'prog_id' => 1,
                'student_id' => Students::inRandomOrder()->first()->id,
                'user_id' => 2,
                'std_act_status' => "confirmed",
                'mt_confirm_status' => "confirmed",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'handled_by' => NULL,
                'location_link' => 'http://example-meeting.com/'.Str::random(10),
                'location_pw' => Str::random(10),
                'prog_dtl_id' => NULL,
                'call_with' => 'mentor',
                'module' => 'life skills',
                'call_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'call_status' => 'waiting'
            ),
            array(
                'prog_id' => 1,
                'student_id' => Students::inRandomOrder()->first()->id,
                'user_id' => 2,
                'std_act_status' => "confirmed",
                'mt_confirm_status' => "confirmed",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'handled_by' => NULL,
                'location_link' => 'http://example-meeting.com/'.Str::random(10),
                'location_pw' => Str::random(10),
                'prog_dtl_id' => NULL,
                'call_with' => 'mentor',
                'module' => 'life skills',
                'call_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'call_status' => 'finished'
            ),
            array(
                'prog_id' => 1,
                'student_id' => Students::inRandomOrder()->first()->id,
                'user_id' => 2,
                'std_act_status' => "reject",
                'mt_confirm_status' => "confirmed",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'handled_by' => NULL,
                'location_link' => 'http://example-meeting.com/'.Str::random(10),
                'location_pw' => Str::random(10),
                'prog_dtl_id' => NULL,
                'call_with' => 'mentor',
                'module' => 'life skills',
                'call_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'call_status' => 'rejected'
            ),
            array(
                'prog_id' => 1,
                'student_id' => Students::inRandomOrder()->first()->id,
                'user_id' => 2,
                'std_act_status' => "cancel",
                'mt_confirm_status' => "confirmed",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'handled_by' => NULL,
                'location_link' => 'http://example-meeting.com/'.Str::random(10),
                'location_pw' => Str::random(10),
                'prog_dtl_id' => NULL,
                'call_with' => 'mentor',
                'module' => 'life skills',
                'call_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'call_status' => 'canceled'
            ),
        );

        for ($i = 0; $i < 15 ; $i++) {
            $data[] = array(
                'prog_id' => 1,
                'student_id' => Students::inRandomOrder()->first()->id,
                'user_id' => 2,
                'std_act_status' => "waiting",
                'mt_confirm_status' => "confirmed",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'handled_by' => NULL,
                'location_link' => 'http://example-meeting.com/'.Str::random(10),
                'location_pw' => Str::random(10),
                'prog_dtl_id' => NULL,
                'call_with' => 'mentor',
                'module' => 'life skills',
                'call_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'call_status' => 'waiting'
            );
        }

        DB::table('student_activities')->insert($data);
    }
}
