<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgrammeSeeder extends Seeder
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
                'prog_mod_id' => 1,
                'prog_name'   => '1-on-1 Mentoring',
                'prog_desc'   => 'Quisque velit nisi, pretium ut lacinia in, elementum id enim.',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s')
            ),
            array(
                'prog_mod_id' => 1,
                'prog_name'   => 'Contact Mentor',
                'prog_desc'   => 'Quisque velit nisi, pretium ut lacinia in, elementum id enim.',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s')
            ),
            array(
                'prog_mod_id' => 2,
                'prog_name' => 'Career Industry Webinar',
                'prog_desc' => 'Quisque velit nisi, pretium ut lacinia in, elementum id enim.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'prog_mod_id' => 2,
                'prog_name' => '1-on-1 Mentoring',
                'prog_desc' => 'Quisque velit nisi, pretium ut lacinia in, elementum id enim.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'prog_mod_id' => 3,
                'prog_name' => 'University Admission',
                'prog_desc' => 'Quisque velit nisi, pretium ut lacinia in, elementum id enim.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'prog_mod_id' => 3,
                'prog_name' => 'Uni Prep Webinar',
                'prog_desc' => 'Quisque velit nisi, pretium ut lacinia in, elementum id enim.',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
        );

        DB::table('programmes')->insert($data);
    }
}
