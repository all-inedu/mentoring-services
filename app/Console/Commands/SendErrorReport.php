<?php

namespace App\Console\Commands;

use App\Http\Controllers\MailLogController;
use Illuminate\Console\Command;

class SendErrorReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:send_error_report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated send error report to tech team';

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
        $mail = new MailLogController;
        return $mail->mail_to_tech();
    }
}
