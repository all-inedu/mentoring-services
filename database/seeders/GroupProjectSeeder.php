<?php

namespace Database\Seeders;

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
            array(
                'student_id' => 1,
                'user_id' => 2,
                'project_name' => 'Dummy Project A',
                'project_type' => 'Group Mentoring',
                'project_desc' => 'Vivamus magna justo, lacinia eget consectetur sed, convallis at tellus. Vivamus suscipit tortor eget felis porttitor volutpat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Curabitur aliquet quam id dui posuere blandit. Proin eget tortor risus. Donec sollicitudin molestie malesuada. Pellentesque in ipsum id orci porta dapibus.',
                'progress_status' => null,
                'status' => 'in progress',
                'owner_type' => 'student',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'student_id' => 2,
                'user_id' => 3,
                'project_name' => 'Dummy Project B',
                'project_type' => 'Group Mentoring',
                'project_desc' => 'Vivamus magna justo, lacinia eget consectetur sed, convallis at tellus. Vivamus suscipit tortor eget felis porttitor volutpat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Curabitur aliquet quam id dui posuere blandit. Proin eget tortor risus. Donec sollicitudin molestie malesuada. Pellentesque in ipsum id orci porta dapibus.',
                'progress_status' => null,
                'status' => 'in progress',
                'owner_type' => 'student',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
        );

        DB::table('group_projects')->insert($data);
    }
}
