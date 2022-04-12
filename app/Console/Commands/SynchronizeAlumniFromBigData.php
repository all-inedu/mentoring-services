<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CRM\ClientController;

class SynchronizeAlumniFromBigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:synchronize_alumni';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated synchronize alumni data from big data daily';

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
        $sync = new ClientController;
        $import_alumni = $sync->synchronize('alumni', 'import', true);
        return $import_alumni;
    }
}
