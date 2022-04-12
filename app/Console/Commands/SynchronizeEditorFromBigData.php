<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CRM\ClientController;

class SynchronizeEditorFromBigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:synchronize_editor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated synchronize editor data from big data daily';

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
        $import_editor = $sync->synchronize('editor', 'import', true);
        return $import_editor;
    }
}
