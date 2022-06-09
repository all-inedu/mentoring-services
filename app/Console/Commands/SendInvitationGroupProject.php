<?php

namespace App\Console\Commands;

use App\Jobs\SendInvitationGroupProject as JobsSendInvitationGroupProject;
use App\Models\GroupProject;
use Illuminate\Console\Command;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Participant;
use Illuminate\Support\Facades\Mail;

class SendInvitationGroupProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:send_invitation_group_project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated sending group project invitation using email';

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
         
        try {
            $details = [
                'subject' => 'You\'ve been invited to join Group Project',
            ];

            JobsSendInvitationGroupProject::dispatch($details)->delay(now()->addSeconds(2));

            Log::channel('groupinvitationlog')->info("Group invitation has been sent");
        } catch (Exception $e) {
            Log::channel('groupinvitationlog')->error($e->getMessage());
        }
        
        echo "Invitee mail send successfully in the background";
    }
}
