<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $data = array(
            array(
                'group_id' => 1,
                'student_id' => 1,
                'contribution_role' => 'lorem ipsum role 1',
                'contribution_description' => 'Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Proin eget tortor risus. Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => 1,
                'mail_sent_status' => 1
            )
        );
    }
}
