<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CRM\ClientController;
use App\Http\Controllers\CRM\V2\ClientController as V2ClientController;
use Exception;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SynchronizeStudentFromBigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:synchronize_student';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated synchronize student data from big data daily';

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
        // $sync = new ClientController;
        $sync = new V2ClientController;
        $this->info(json_encode($sync->synchronize('student', 'import', true)));

        return Command::SUCCESS;
    }
}
