<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CRM\ClientController;
use App\Http\Controllers\CRM\V2\ClientController as V2ClientController;

class SynchronizeMentorFromBigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:synchronize_mentor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated synchronize mentor data from big data daily';

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
        $this->info(json_encode($sync->synchronize('mentor', 'import', true)));
        
        return COMMAND::SUCCESS;
    }
}
