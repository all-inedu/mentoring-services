<?php

namespace App\Jobs;

use App\Models\GroupMeeting;
use App\Models\GroupProject;
use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\StudentAttendances;
use App\Models\UserAttendances;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Http\Controllers\MailLogController;

class ReminderNextGroupMeeting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200; // 2 hours
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $meetings = GroupMeeting::where('mail_sent', 0)->get();
        foreach ($meetings as $meeting_detail) {
            $group_id = $meeting_detail->group_id;
            $all_email = array();

            $group_info = GroupProject::find($group_id);

            $participants = $meeting_detail->student_attendances()->where('mail_sent', 0)->where('attend_status', 0)->get();
            $mentors = $meeting_detail->user_attendances()->where('mail_sent', 0)->where('attend_status', 0)->get();

            //*email to participant
            foreach ($participants as $k => $v) {
                // array_push($all_email, $v->email);
                $encrypted_data = array(
                    'attend_id' => $v->pivot->id,
                    'group_meet_id' => $meeting_detail->id,
                );
                
                $token = Crypt::encrypt($encrypted_data);
                $email = $v->email;
                $name = $v->first_name.' '.$v->last_name;
                $subject = "You've a new group meeting";

                Mail::send('templates.mail.next-group-meeting-announcement', ['name' => $name, 'person' => 'student', 'group_info' => $group_info, 'meeting_detail' => $meeting_detail, 'token' => $token],
                    function($mail) use ($email, $name, $subject) {
                        $mail->from(getenv('FROM_EMAIL_ADDRESS'), "no-reply@all-inedu.com");
                        $mail->to($email, $name);
                        $mail->subject($subject);
                    }); 

                    

                if (count(Mail::failures()) > 0) { 

                    // save to log mail admin
                    // save only if failure to sent
                    $log = array(
                        'sender'    => 'system',
                        'recipient' => $email,
                        'subject'   => 'Sending reminder to join group meeting to Student',
                        'message'   => json_encode($meeting_detail),
                        'date_sent' => Carbon::now(),
                        'status'    => "not delivered",
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    );
                    $save_log = new MailLogController;
                    $save_log->saveLogMail($log);
                    
                    foreach (Mail::failures() as $email_address) {
                        Log::channel('group_meeting_reminder_log')->error("Sending reminder mail failures to ". $email_address.' from GM : '.$group_info->project_name.' with id '.$group_info->id.', scheduled at '.$meeting_detail->meeting_date);
                    }
                    continue;
                } 

                //* update sent mail to 1 if mail successfully delivered
                // DB::beginTransaction();
                try {
                    
                    $attendances = StudentAttendances::find($v->pivot->id);
                    $attendances->mail_sent = 1;
                    $attendances->save();
                    // DB::commit();
                } catch (Exception $e) {
                    // DB::rollBack();
                    Log::channel('group_meeting_reminder_log')->error('Update Student Attendances Mail Sent Issue : [ Attend_id '.$v->pivot->id.' ] '.$e->getMessage());
                }
            }

            //* email to mentor
            foreach ($mentors as $k => $v) {
                // array_push($all_email, $v->email);
                $encrypted_data = array(
                    'attend_id' => $v->pivot->id,
                    'group_meet_id' => $meeting_detail->id,
                );

                $token = Crypt::encrypt($encrypted_data);
                $email = $v->email;
                $name = $v->first_name.' '.$v->last_name;
                $subject = "Your student set a new group meeting";

                Mail::send('templates.mail.next-group-meeting-announcement', ['name' => $name, 'person' => 'mentor', 'group_info' => $group_info, 'meeting_detail' => $meeting_detail, 'token' => $token],
                    function($mail) use ($email, $name, $subject) {
                        $mail->from(getenv('FROM_EMAIL_ADDRESS'), "no-reply@all-inedu.com");
                        $mail->to($email, $name);
                        $mail->subject($subject);
                    }); 
                
                if (count(Mail::failures()) > 0) { 

                    // save to log mail admin
                    // save only if failure to sent
                    $log = array(
                        'sender'    => 'system',
                        'recipient' => $email,
                        'subject'   => 'Sending reminder to join group meeting to Mentor',
                        'message'   => json_encode($meeting_detail),
                        'date_sent' => Carbon::now(),
                        'status'    => "not delivered",
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    );
                    $save_log = new MailLogController;
                    $save_log->saveLogMail($log);

                    foreach (Mail::failures() as $email_address) {
                        Log::channel('group_meeting_reminder_log')->error("Sending reminder mail failures to ". $email_address.' from GM : '.$group_info->project_name.' with id '.$group_info->id.', scheduled at '.$meeting_detail->meeting_date);
                    }
                    continue;
                } 

                //* update sent mail to 1 if mail successfully delivered
                // DB::beginTransaction();
                try {
                    
                    $attendances = UserAttendances::find($v->pivot->id);
                    $attendances->mail_sent = 1;
                    $attendances->save();
                    // DB::commit();
                } catch (Exception $e) {
                    // DB::rollBack();
                    Log::channel('group_meeting_reminder_log')->error('Update User Attendances Mail Sent Issue : [ Attend_id '.$v->pivot->id.' ] '.$e->getMessage());
                }
            }
        
            Log::channel('group_meeting_reminder_log')->info('Reminder for scheduled group meeting has been sent to students and mentors');

            $meeting_detail->mail_sent = 1;
            $meeting_detail->save();
        }
    }
}