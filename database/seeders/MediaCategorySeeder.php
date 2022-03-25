<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MediaCategorySeeder extends Seeder
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
                'name' => 'Media Category 1',
                'terms' => 'Terms example 1',
                'status' => 1
            ),
            array(
                'name' => 'Media Category 2',
                'terms' => 'Terms example 2',
                'status' => 1
            )
        );

        DB::table('media_categories')->insert($data);
    }
}
