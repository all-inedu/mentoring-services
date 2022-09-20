<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GroupProject;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Participant;

class TestController extends Controller
{

    public function index()
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
                $group_owner = $group_info->owner_type;
                
                $today = date('Y-m-d');
                // get participant by todays date only
                // find where system hasn't sending the email ( 0 not delivered, 1 delivered )
    
                if ($group_info->group_participant()->wherePivot('mail_sent_status', 0)->wherePivot('status', 0)->count() > 0) {
                    $participants = $group_info->group_participant()->wherePivot('mail_sent_status', 0)->wherePivot('status', 0)->get();
    
                    if ($group_owner == "student")
                        $mail_data['student_owner_name'] = $group_info->students->first_name.' '.$group_info->students->last_name;                
                    
                        $mail_data['group_detail'] = array(
                        'project_name' => $group_info->project_name,
                        // 'project_type' => $group_info->project_type,
                        'project_desc' => $group_info->project_desc,
                        'project_owner' => $group_info->student_id != NULL ? $group_info->students->first_name.' '.$group_info->students->first_name : $group_info->users->first_name.' '.$group_info->users->last_name,
                    );
    
                    foreach ($participants as $student) {
                        $mail_data['student_detail'] = array(
                            'participant_id' => $student->pivot->id,
                            'full_name' => $student->first_name.' '.$student->last_name,
                            'email' => $student->email,
                        );
    
                        $resource_view = $group_owner == "student" ? 'templates.mail.group-invitation' : 'templates.mail.to-mentees.mention-new-group-project';
                        
                        // insert into variable so the mail data can be called inside mail function
                        Mail::send($resource_view, ['group_info' => $mail_data], function($mail) use ($mail_data) {
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
            return $e->getMessage();
            Log::channel('groupinvitationlog')->error("Failed to send group invitation : ".$e->getMessage());
        }
        return 1;
    }
}
