<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TodosSeeder extends Seeder
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
                'student_mentors_id' => 1,
                'task_name' => 'Todos Dummy no. 1',
                'description' => 'Curabitur aliquet quam id dui posuere blandit. Sed porttitor lectus nibh. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Nulla quis lorem ut libero malesuada feugiat. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula.',
                'due_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'status' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            array(
                'student_mentors_id' => 1,
                'task_name' => 'Todos Dummy no. 2',
                'description' => 'Curabitur aliquet quam id dui posuere blandit. Sed porttitor lectus nibh. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Nulla quis lorem ut libero malesuada feugiat. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula.',
                'due_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            array(
                'student_mentors_id' => 1,
                'task_name' => 'Todos Dummy no. 3',
                'description' => 'Curabitur aliquet quam id dui posuere blandit. Sed porttitor lectus nibh. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Nulla quis lorem ut libero malesuada feugiat. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula.',
                'due_date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-d H:i:s')))).' 10:00:00',
                'status' => 2,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
        );

        DB::table('plan_to_do_lists')->insert($data);
    }
}
