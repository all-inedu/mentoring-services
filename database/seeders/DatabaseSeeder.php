<?php

namespace Database\Seeders;

use Database\Seeders\V2\ProgrammeDetailSeeder as V2ProgrammeDetailSeeder;
use Database\Seeders\V2\ProgrammeSeeder as V2ProgrammeSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            UserAccessSeeder::class,
            // ProgrammeModuleSeeder::class,
            // ProgrammeSeeder::class,
            V2ProgrammeSeeder::class,
            V2ProgrammeDetailSeeder::class,
            UserRoleSeeder::class,
            StudentSeeder::class,
            StudentActivitiesSeeder::class
        ]);
    }
}
