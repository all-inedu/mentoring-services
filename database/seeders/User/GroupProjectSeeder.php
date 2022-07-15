<?php

namespace Database\Seeders\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = array(
                'student_id' => NULL,
                'user_id' => 2,
                'project_name' => 'Mentor Dummy Project 1',
                'project_type' => 'Group Mentoring',
                'project_desc' => 'Vivamus magna justo, lacinia eget consectetur sed, convallis at tellus. Vivamus suscipit tortor eget felis porttitor volutpat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Curabitur aliquet quam id dui posuere blandit. Proin eget tortor risus. Donec sollicitudin molestie malesuada. Pellentesque in ipsum id orci porta dapibus.',
                'progress_status' => NULL,
                'status' => 'in progress',
                'owner_type' => 'mentor',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );

        $inserted_id = DB::table('group_projects')->insertGetId($data);

        DB::table('participants')->insert(array(
            array(
                'group_id' => $inserted_id,
                'student_id' => 1,
                'contribution_role' => NULL,
                'contribution_description' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => 0,
                'mail_sent_status' => 0,
            ),
            array(
                'group_id' => $inserted_id,
                'student_id' => 3,
                'contribution_role' => NULL,
                'contribution_description' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => 1,
                'mail_sent_status' => 1,
            ),
        ));
            
        $inserted_group_meeting_id = [];
        $group_meeting_data = array(
            array(
                'group_id' => $inserted_id,
                'meeting_date' => date('Y-m-d', strtotime("+1 day", strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'meeting_subject' => 'Group Meeting Dummy 1',
                'status' => 2,
                'mail_sent' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'group_id' => $inserted_id,
                'meeting_date' => date('Y-m-d H:i:s'),
                'meeting_subject' => 'Group Meeting Dummy 2',
                'status' => 1,
                'mail_sent' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'group_id' => $inserted_id,
                'meeting_date' => date('Y-m-d', strtotime("+1 day", strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'meeting_subject' => 'Group Meeting Dummy 3',
                'status' => 0,
                'mail_sent' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
        );
        for ($i = 0; $i < 3; $i++) {
            $inserted_group_meeting = DB::table('group_meetings')->insertGetId($group_meeting_data[$i]);
            $inserted_group_meeting_id[$i] = $inserted_group_meeting;
        }

        DB::table('student_attendances')->insert(array(
            array(
                'student_id' => 1,
                'group_meet_id' => $inserted_group_meeting_id[2],
                'attend_status' => 0,
                'mail_sent' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'student_id' => 3,
                'group_meet_id' => $inserted_group_meeting_id[2],
                'attend_status' => 0,
                'mail_sent' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
        ));
        
        DB::table('user_attendances')->insert(array(
            array(
                'user_id' => 2,
                'group_meet_id' => $inserted_group_meeting_id[2],
                'attend_status' => 1,
                'mail_sent' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            )
        ));
    }
}
