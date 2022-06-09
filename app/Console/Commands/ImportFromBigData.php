<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CRM\ClientController;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class ImportFromBigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:import_big_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize alumni, editor, mentor, student';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            //* import mentor
            $sync = new ClientController;
            $import_mentor = $sync->synchronize('mentor', 'import', true);

            //* import editor
            $import_editor = $sync->synchronize('editor', 'import', true);

            //* import alumni
            $import_alumni = $sync->synchronize('alumni', 'import', true);

            //* import student
            $import_student = $sync->synchronize('student', 'import', true);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('scheduler')->error($e->getMessage());
        }

        Log::channel('scheduler')->info('Data has been synced');
        return 1;
        
    }
}
