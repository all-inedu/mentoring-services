<?php
//! not used anymore
//! because already moved to command


namespace App\Jobs;

use App\Models\GroupProject;
use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\DB;

class SendInvitationGroupProject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $details;
    public $timeout = 7200; // 2 hours

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
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
            
            // find where system hasn't sending the email ( 0 not delivered, 1 delivered )
            if ($participants = $group_info->group_participant->where('mail_sent_status', 0)) {

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
                        foreach (Mail::failures() as $email_address) {
                            Log::channel('groupinvitationlog_detail')->error("Sending invitee mail failures to ". $email_address);
                        }
                        continue;
                    }
                        
                    try {

                        // changed sent mail status to 1 if mail delivered
                        $updated_participant = Participant::where('group_id', $group_info->id)->where('student_id', $student->id)->first();
                        $updated_participant->mail_sent_status = 1;
                        $updated_participant->save();
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error("Changed mail sent status participant issue : ". $e->getMessage());
                    }
                    
                }
            }
        }

        

        // return Mail::failures() ? true : false;
    }
}
