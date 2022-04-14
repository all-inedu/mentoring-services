<?php

namespace App\Console\Commands;

use App\Http\Controllers\TransactionController;
use Illuminate\Console\Command;

class TransactionPaymentChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:payment_checker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated cancel transaction that hasn\'t paid before 24 hours';

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

        $checker = new TransactionController;
        return $checker->payment_checker();
    }
}
