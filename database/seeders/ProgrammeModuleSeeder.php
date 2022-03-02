<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgrammeModuleSeeder extends Seeder
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
                'prog_mod_name' => 'Life Skills',
                'prog_mod_desc' => 'Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a.',
                'prog_mod_status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'prog_mod_name' => 'Career Exploration',
                'prog_mod_desc' => 'Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a.',
                'prog_mod_status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'prog_mod_name' => 'University Admission',
                'prog_mod_desc' => 'Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a.',
                'prog_mod_status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array(
                'prog_mod_name' => 'Life University',
                'prog_mod_desc' => 'Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a.',
                'prog_mod_status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),

        );

        DB::table('programme_modules')->insert($data);
    }
}
