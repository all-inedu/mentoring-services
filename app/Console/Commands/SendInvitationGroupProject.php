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
use Illuminate\Support\Carbon;
use App\Http\Controllers\MailLogController;

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
            // $details = [
            //     'subject' => 'You\'ve been invited to join Group Project',
            // ];

            // JobsSendInvitationGroupProject::dispatch($details)->delay(now()->addSeconds(60));
            
            // collect all the group project in the database
            
            $group_project = GroupProject::orderBy('created_at', 'asc')->get();
            $mail_data = array(
                'subject' => 'You\'ve been invited to join Group Project',
            );

            foreach ($group_project as $group_info) {
                $mail_data['group_detail'] = array(
                    'project_name' => $group_info->project_name,
                    'project_type' => $group_info->project_type,
                    'project_desc' => $group_info->project_desc,
                    'project_owner' => $group_info->student_id != NULL ? $group_info->students->first_name.' '.$group_info->students->first_name : $group_info->users->first_name.' '.$group_info->users->last_name,
                );
                
                $today = date('Y-m-d');
                // get participant by todays date only
                // find where system hasn't sending the email ( 0 not delivered, 1 delivered )
                if ($participants = $group_info->group_participant()->wherePivot('mail_sent_status', 0)->wherePivot('status', 0)->get()) {
                    echo json_encode($participants);exit;
                    foreach ($participants as $student) {
                        $mail_data['student_detail'] = array(
                            'participant_id' => $student->pivot->id,
                            'full_name' => $student->first_name.' '.$student->last_name,
                            'email' => $student->email,
                        );

                        // insert into variable so the mail data can be called inside mail function
                        Mail::send('templates.mail.group-invitation', ['group_info' => $mail_data], function($mail) use ($mail_data) {
                            $mail->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                            $mail->to($mail_data['student_detail']['email'], $mail_data['student_detail']['full_name']);
                            $mail->subject($mail_data['subject']);
                        });

                        // check if the mail has been delivered or not
                        // if mail not delivered
                        if (count(Mail::failures()) > 0) { 

                            // save to log mail admin
                            // save only if failure to sent
                            $log = array(
                                'sender'    => 'system',
                                'recipient' => $student->email,
                                'subject'   => 'Sending invitation to join group project',
                                'message'   => json_encode($mail_data),
                                'date_sent' => Carbon::now(),
                                'status'    => "not delivered",
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            );
                            $save_log = new MailLogController;
                            $save_log->saveLogMail($log);

                            foreach (Mail::failures() as $email_address) {
                                Log::channel('groupinvitationlog_detail')->error("Sending invitee mail failures to ". $email_address.' from GP : '.$group_info->project_name.' with id '.$group_info->id);
                            }
                            continue;
                        }

                        // changed sent mail status to 1 if mail delivered
                        $updated_participant = Participant::where('group_id', $group_info->id)->where('student_id', $student->id)->where('mail_sent_status', 0)->first();
                        $updated_participant->mail_sent_status = 1;
                        if (!$updated_participant->save()) {
                            Log::channel('groupinvitationlog_detail')->error("Changed mail sent status participant issue with mail id : ".$updated_participant->id);
                        }                                
                    }
                }
            }
            
            Log::channel('groupinvitationlog')->info("Group invitation has been sent");
        } catch (Exception $e) {
            Log::channel('groupinvitationlog')->error("Failed to send group invitation : ".$e->getMessage());
        }
        return 1;
        // echo "Invitee mail send successfully in the background";
    }
}
