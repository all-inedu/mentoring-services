<?php

namespace Database\Seeders\V2;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgrammeDetailSeeder extends Seeder
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
                'prog_id' => 3,
                'dtl_category' => 'career-industry-webinar',
                'dtl_name' => 'Career Industry Webinar',
                'dtl_desc' => 'Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.',
                'dtl_price' => 489000,
                'dtl_video_link' => 'https://www.youtube.com/',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'prog_id' => 3,
                'dtl_category' => 'uni-preparation-webinar',
                'dtl_name' => 'University Preparation Webinar',
                'dtl_desc' => 'Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.',
                'dtl_price' => 489000,
                'dtl_video_link' => 'https://www.youtube.com/',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'prog_id' => 4,
                'dtl_category' => 'career-bootcamp',
                'dtl_name' => 'Fullstack Bootcamp 2022',
                'dtl_desc' => 'Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.',
                'dtl_price' => 489000,
                'dtl_video_link' => 'https://www.youtube.com/',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
        );

        DB::table('programme_details')->insert($data);
    }
}
